<?php

namespace Tests\Feature\Commandes;

use App\Models\Article;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\Concerns\PrepareLesCommandes;
use Tests\TestCase;

/** Confirmation (panier → en_attente) : contrôle et débit des points, totaux figés. */
class ConfirmationCommandeTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    private const ETUDIANT = 'etudiant@ucac-icam.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->autoriserConfirmationDirecte();
    }

    private function confirmer(int $idCommande, string $email = self::ETUDIANT)
    {
        $this->app['auth']->forgetGuards();

        return $this->enTantQue($email)->postJson("/api/commandes/{$idCommande}/confirmer");
    }

    public function test_la_confirmation_rend_la_commande_visible_du_personnel(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);

        // Avant confirmation : le tableau du personnel ne la voit pas.
        $this->enTantQue('paul@zeduc.com')->getJson('/api/staff/commandes')->assertJsonCount(0, 'a_preparer');

        $this->confirmer($commande->id_commande)->assertOk()->assertJsonPath('statut', 'en_attente');

        $this->app['auth']->forgetGuards();
        $this->enTantQue('paul@zeduc.com')->getJson('/api/staff/commandes')->assertJsonCount(1, 'a_preparer');
    }

    public function test_la_confirmation_est_inscrite_au_journal(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);
        $this->confirmer($commande->id_commande)->assertOk();

        $this->assertSame(
            ['panier', 'en_attente'],
            DB::table('historique_commande')->where('id_commande', $commande->id_commande)->orderBy('id_historique')->pluck('statut_commande')->all(),
        );
    }

    public function test_les_points_sont_debites_a_la_confirmation_et_pas_avant(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $this->assertSame(45, $this->solde(self::ETUDIANT), 'Rien n\'est débité à la création.');

        $this->confirmer($commande->id_commande)
            ->assertOk()
            ->assertJsonPath('points_utilises', 45)
            ->assertJsonPath('reduction_points', 3000)
            ->assertJsonPath('total', 500);

        $this->assertSame(0, $this->solde(self::ETUDIANT));
        $this->assertDatabaseHas('mouvement_points', [
            'id_commande' => $commande->id_commande, 'type_mouvement' => 'utilisation', 'nombre_points' => -45,
        ]);
    }

    public function test_le_solde_reste_egal_a_la_somme_du_grand_livre(): void
    {
        // Point de départ : on inscrit les 45 points initiaux dans le grand livre (comme le ferait l'import).
        DB::table('mouvement_points')->insert(['type_mouvement' => 'ajustement', 'nombre_points' => 45, 'id_etudiant' => $this->compte(self::ETUDIANT)->id_utilisateur]);

        $commande = $this->creerCommande(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $this->confirmer($commande->id_commande)->assertOk();

        $somme = (int) DB::table('mouvement_points')->where('id_etudiant', $this->compte(self::ETUDIANT)->id_utilisateur)->sum('nombre_points');
        $this->assertSame($somme, $this->solde(self::ETUDIANT));
    }

    public function test_une_commande_sans_points_ne_touche_pas_au_solde(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);
        $this->confirmer($commande->id_commande)->assertOk();

        $this->assertSame(45, $this->solde(self::ETUDIANT));
        $this->assertSame(0, DB::table('mouvement_points')->count());
    }

    public function test_une_commande_couverte_a_100_pour_cent_par_les_points_est_confirmable(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Pizza La Terrasse' => 1], 'sur_place', ['utiliser_points' => true]);

        $this->confirmer($commande->id_commande)->assertOk()->assertJsonPath('total', 0)->assertJsonPath('statut', 'en_attente');
        $this->assertSame(0, $this->solde(self::ETUDIANT));
    }

    public function test_on_ne_peut_pas_confirmer_deux_fois(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $this->confirmer($commande->id_commande)->assertOk();

        $this->confirmer($commande->id_commande)->assertStatus(409);
        // Les points n'ont pas été débités une seconde fois.
        $this->assertSame(1, DB::table('mouvement_points')->where('type_mouvement', 'utilisation')->count());
    }

    public function test_si_le_solde_a_baisse_entre_la_creation_et_la_confirmation_rien_n_est_modifie(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]); // 45 pts prévus
        DB::table('etudiant')->update(['solde_points' => 15]); // entre-temps l'étudiant a dépensé 30 points ailleurs

        $this->confirmer($commande->id_commande)
            ->assertUnprocessable()
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Solde de points insuffisant'));

        // Tout est annulé : la commande reste en attente de paiement, aucun mouvement, solde intact.
        $this->assertSame('panier', $commande->fresh()->statut_commande);
        $this->assertSame(0, DB::table('mouvement_points')->count());
        $this->assertSame(15, $this->solde(self::ETUDIANT));
    }

    public function test_les_memes_points_ne_peuvent_pas_payer_deux_commandes(): void
    {
        // Deux paniers qui prévoient chacun d'utiliser les 45 points.
        $premiere = $this->creerCommande(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $seconde = $this->creerCommande(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);

        $this->confirmer($premiere->id_commande)->assertOk();
        $this->confirmer($seconde->id_commande)->assertUnprocessable();

        $this->assertSame(0, $this->solde(self::ETUDIANT));
        $this->assertSame('panier', $seconde->fresh()->statut_commande);
    }

    public function test_la_confirmation_est_refusee_si_un_plat_est_passe_en_rupture(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);
        Article::where('nom_article', 'Poulet Braisé')->update(['disponible' => false]);

        $this->confirmer($commande->id_commande)
            ->assertStatus(409)
            ->assertJsonPath('message', "« Poulet Braisé » n'est plus disponible.");
        $this->assertSame('panier', $commande->fresh()->statut_commande);
    }

    public function test_une_commande_trop_ancienne_est_refusee(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);
        $commande->update(['date_commande' => now()->subMinutes(121)]);

        $this->confirmer($commande->id_commande)->assertStatus(409)->assertJsonPath('message', 'Cette commande a expiré : veuillez la refaire.');
    }

    public function test_le_total_confirme_utilise_les_prix_figes_a_la_creation(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 2]);
        Article::where('nom_article', 'Poulet Braisé')->update(['prix_unitaire' => 9000]); // augmentation entre-temps

        $this->confirmer($commande->id_commande)->assertOk()->assertJsonPath('total', 5000);
    }

    public function test_sans_mode_de_test_la_confirmation_exige_le_paiement(): void
    {
        $this->autoriserConfirmationDirecte(false);
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);

        $this->confirmer($commande->id_commande)
            ->assertForbidden()
            ->assertJsonPath('message', 'Le paiement est requis pour confirmer une commande.');
        $this->assertSame('panier', $commande->fresh()->statut_commande);
    }

    public function test_on_ne_peut_pas_confirmer_la_commande_d_un_autre(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);

        $this->confirmer($commande->id_commande, $this->autreEtudiant())->assertNotFound();
        $this->assertSame('panier', $commande->fresh()->statut_commande);
    }

    public function test_les_commandes_non_confirmees_n_apparaissent_pas_dans_le_comptage_du_personnel(): void
    {
        $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);

        $this->assertSame(1, Commande::where('statut_commande', 'panier')->count());
        $this->enTantQue('paul@zeduc.com')->getJson('/api/staff/commandes')
            ->assertOk()->assertJsonCount(0, 'a_preparer')->assertJsonCount(0, 'en_cuisine')->assertJsonCount(0, 'pret');
    }
}
