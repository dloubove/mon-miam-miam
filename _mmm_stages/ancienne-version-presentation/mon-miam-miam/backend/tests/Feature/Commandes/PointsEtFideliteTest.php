<?php

namespace Tests\Feature\Commandes;

use App\Exceptions\RegleMetierException;
use App\Models\MouvementPoints;
use App\Services\FideliteService;
use App\Services\PointsService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLesCommandes;
use Tests\TestCase;

/** Grand livre des points et règles de fidélité. */
class PointsEtFideliteTest extends TestCase
{
    use GereLesConnexions, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    private function etudiant(): int
    {
        return $this->compte('etudiant@ucac-icam.com')->id_utilisateur;
    }

    public function test_un_gain_met_a_jour_le_solde_et_le_grand_livre(): void
    {
        app(PointsService::class)->enregistrer($this->etudiant(), 'gain_mini_jeu', 5);

        $this->assertSame(50, $this->solde('etudiant@ucac-icam.com'));
        $this->assertDatabaseHas('mouvement_points', ['id_etudiant' => $this->etudiant(), 'type_mouvement' => 'gain_mini_jeu', 'nombre_points' => 5]);
    }

    public function test_on_ne_peut_pas_depenser_plus_que_son_solde(): void
    {
        $commande = $this->creerCommande('etudiant@ucac-icam.com', ['Poulet Braisé' => 1]);

        try {
            app(PointsService::class)->enregistrer($this->etudiant(), 'utilisation', -46, $commande->id_commande);
            $this->fail('Une exception « solde insuffisant » était attendue.');
        } catch (RegleMetierException $e) {
            $this->assertSame(422, $e->statutHttp);
            $this->assertStringContainsString('Solde de points insuffisant : 45 disponible(s), 46 demandé(s)', $e->getMessage());
        }

        $this->assertSame(45, $this->solde('etudiant@ucac-icam.com'));
        $this->assertSame(0, MouvementPoints::count());
    }

    public function test_le_grand_livre_est_immuable(): void
    {
        $mouvement = app(PointsService::class)->enregistrer($this->etudiant(), 'gain_mini_jeu', 5);

        $this->expectException(LogicException::class);
        $mouvement->update(['nombre_points' => 500]);
    }

    public function test_un_mouvement_ne_peut_pas_etre_supprime(): void
    {
        $mouvement = app(PointsService::class)->enregistrer($this->etudiant(), 'gain_mini_jeu', 5);

        try {
            $mouvement->delete();
            $this->fail('La suppression aurait dû être refusée.');
        } catch (LogicException) {
            $this->assertDatabaseHas('mouvement_points', ['id_mouvement' => $mouvement->id_mouvement]);
        }
    }

    public function test_la_base_refuse_un_mouvement_au_mauvais_signe_et_le_solde_n_est_pas_modifie(): void
    {
        $commande = $this->creerCommande('etudiant@ucac-icam.com', ['Poulet Braisé' => 1]);

        // Une « utilisation » doit être négative : la contrainte de la base refuse un +15.
        $this->expectException(QueryException::class);
        try {
            app(PointsService::class)->enregistrer($this->etudiant(), 'utilisation', 15, $commande->id_commande);
        } finally {
            $this->assertSame(45, $this->solde('etudiant@ucac-icam.com'), 'Le solde doit rester intact (transaction annulée).');
        }
    }

    public function test_gain_de_points_un_par_tranche_entiere_de_1000_fcfa(): void
    {
        $fidelite = app(FideliteService::class);

        $this->assertSame(0, $fidelite->pointsGagnes(999));
        $this->assertSame(1, $fidelite->pointsGagnes(1000));
        $this->assertSame(3, $fidelite->pointsGagnes(3999));
        $this->assertSame(0, $fidelite->pointsGagnes(0));
    }

    public function test_le_gain_suit_le_parametre_modifiable_par_l_administrateur(): void
    {
        DB::table('parametre')->where('cle_parametre', 'points_par_1000_fcfa')->update(['valeur_parametre' => '2']);

        $this->assertSame(6, app(FideliteService::class)->pointsGagnes(3000));
    }

    public function test_controle_du_nombre_de_points_a_utiliser(): void
    {
        $fidelite = app(FideliteService::class);

        $this->assertNull($fidelite->reductionPour(10), 'Sous le seuil (15).');
        $this->assertNull($fidelite->reductionPour(20), 'Pas un multiple de la tranche (15).');
        $this->assertSame(1000, $fidelite->reductionPour(15));
        $this->assertSame(2000, $fidelite->reductionPour(30));
    }

    public function test_un_parametre_de_fidelite_absent_est_une_erreur_explicite(): void
    {
        DB::table('parametre')->where('cle_parametre', 'fcfa_pour_15_points')->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fcfa_pour_15_points');
        app(FideliteService::class)->utilisation(3000, 45);
    }

    public function test_les_lignes_d_une_commande_confirmee_sont_immuables(): void
    {
        $commande = $this->commandeConfirmee('etudiant@ucac-icam.com', ['Poulet Braisé' => 1]);
        $ligne = $commande->lignes()->first();

        try {
            $ligne->quantite = 10;
            $ligne->save();
            $this->fail('La modification aurait dû être refusée.');
        } catch (LogicException $e) {
            $this->assertSame('Les lignes d\'une commande confirmée sont immuables.', $e->getMessage());
        }

        try {
            $ligne->delete();
            $this->fail('La suppression aurait dû être refusée.');
        } catch (LogicException) {
            $this->assertSame(1, $commande->lignes()->count());
        }
    }
}
