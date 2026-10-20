<?php

namespace Tests\Feature\Commandes;

use App\Models\Utilisateur;
use App\Services\CommandeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\Concerns\PrepareLesCommandes;
use Tests\TestCase;

/** Ce que voit l'étudiant : ses commandes, le suivi, l'annulation. */
class SuiviEtudiantTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    private const ETUDIANT = 'etudiant@ucac-icam.com';

    private function personnel(): Utilisateur
    {
        return $this->compte('paul@zeduc.com');
    }

    public function test_je_ne_vois_que_mes_commandes_les_plus_recentes_d_abord(): void
    {
        $ancienne = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1]);
        $recente = $this->commandeConfirmee(self::ETUDIANT, ['Poisson Braisé' => 1]);
        $this->commandeConfirmee($this->autreEtudiant(), ['Foléré' => 1]); // celle d'un autre étudiant

        $reponse = $this->enTantQue(self::ETUDIANT)->getJson('/api/commandes')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);

        $this->assertSame([$recente->id_commande, $ancienne->id_commande], array_column($reponse->json('data'), 'id'));
    }

    public function test_le_filtre_etat_separe_en_cours_historique_et_a_payer(): void
    {
        $service = app(CommandeService::class);
        $enCours = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1]);
        $terminee = $this->commandeConfirmee(self::ETUDIANT, ['Poisson Braisé' => 1]);
        $service->demarrer($terminee, $this->personnel());
        $service->marquerPrete($terminee->fresh(), $this->personnel());
        $service->cloturer($terminee->fresh(), $this->personnel());
        $aPayer = $this->creerCommande(self::ETUDIANT, ['Foléré' => 1]);

        $ids = fn (string $etat) => array_column($this->enTantQue(self::ETUDIANT)->getJson("/api/commandes?etat={$etat}")->assertOk()->json('data'), 'id');

        $this->assertSame([$enCours->id_commande], $ids('en_cours'));
        $this->app['auth']->forgetGuards();
        $this->assertSame([$terminee->id_commande], $ids('terminees'));
        $this->app['auth']->forgetGuards();
        $this->assertSame([$aPayer->id_commande], $ids('a_payer'));
        $this->app['auth']->forgetGuards();
        $this->enTantQue(self::ETUDIANT)->getJson('/api/commandes?etat=nimporte')->assertUnprocessable();
    }

    public function test_la_liste_est_paginee(): void
    {
        foreach (range(1, 3) as $i) {
            $this->creerCommande(self::ETUDIANT, ['Foléré' => $i]);
        }

        $this->enTantQue(self::ETUDIANT)->getJson('/api/commandes?par_page=2')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 2);
    }

    public function test_le_detail_d_une_commande_contient_la_frise_de_suivi(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1], 'livraison');
        app(CommandeService::class)->demarrer($commande, $this->personnel());

        $etapes = collect($this->enTantQue(self::ETUDIANT)->getJson("/api/commandes/{$commande->id_commande}")->assertOk()->json('etapes'))->keyBy('statut');

        $this->assertTrue($etapes['en_attente']['atteinte']);
        $this->assertNotNull($etapes['en_attente']['date']);
        $this->assertTrue($etapes['en_preparation']['atteinte']);
        $this->assertFalse($etapes['prete']['atteinte']);
        $this->assertNull($etapes['prete']['date']);
        // En livraison, la dernière étape s'appelle « Livrée » ; sur place, « Servie sur place ».
        $this->assertSame('Livrée', $etapes['livree']['libelle']);
        $this->assertArrayNotHasKey('servie', $etapes->all());
    }

    public function test_la_derniere_etape_sur_place_est_servie(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1], 'sur_place');

        $etapes = collect($this->enTantQue(self::ETUDIANT)->getJson("/api/commandes/{$commande->id_commande}")->json('etapes'))->keyBy('statut');
        $this->assertSame('Servie sur place', $etapes['servie']['libelle']);
    }

    public function test_je_ne_peux_pas_voir_la_commande_d_un_autre(): void
    {
        $commande = $this->commandeConfirmee($this->autreEtudiant(), ['Poulet Braisé' => 1]);

        // 404 et non 403 : on ne révèle même pas que cette commande existe.
        $this->enTantQue(self::ETUDIANT)->getJson("/api/commandes/{$commande->id_commande}")->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->enTantQue(self::ETUDIANT)->getJson('/api/commandes/99999')->assertNotFound();
    }

    public function test_annuler_une_commande_non_payee(): void
    {
        $commande = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1]);

        $this->enTantQue(self::ETUDIANT)->postJson("/api/commandes/{$commande->id_commande}/annuler")
            ->assertOk()->assertJsonPath('statut', 'annulee');
    }

    public function test_annuler_une_commande_confirmee_rend_les_points_par_un_mouvement_correctif(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $this->assertSame(0, $this->solde(self::ETUDIANT));

        $this->enTantQue(self::ETUDIANT)->postJson("/api/commandes/{$commande->id_commande}/annuler")
            ->assertOk()->assertJsonPath('statut', 'annulee');

        $this->assertSame(45, $this->solde(self::ETUDIANT));
        // Le grand livre garde la trace des DEUX mouvements : la dépense et sa correction.
        $this->assertSame([-45, 45], DB::table('mouvement_points')->where('id_commande', $commande->id_commande)->orderBy('id_mouvement')->pluck('nombre_points')->all());
    }

    public function test_on_ne_peut_pas_annuler_deux_fois_ni_recuperer_deux_fois_ses_points(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $this->enTantQue(self::ETUDIANT)->postJson("/api/commandes/{$commande->id_commande}/annuler")->assertOk();

        $this->app['auth']->forgetGuards();
        $this->enTantQue(self::ETUDIANT)->postJson("/api/commandes/{$commande->id_commande}/annuler")->assertStatus(409);
        $this->assertSame(45, $this->solde(self::ETUDIANT), 'Les points ne doivent être rendus qu\'une fois.');
    }

    public function test_l_etudiant_ne_peut_plus_annuler_une_commande_en_preparation(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1]);
        app(CommandeService::class)->demarrer($commande, $this->personnel());

        $this->enTantQue(self::ETUDIANT)->postJson("/api/commandes/{$commande->id_commande}/annuler")
            ->assertStatus(409)
            ->assertJsonPath('message', "Votre commande est déjà en préparation : contactez le restaurant pour l'annuler.");
        $this->assertSame('en_preparation', $commande->fresh()->statut_commande);
    }

    public function test_je_ne_peux_pas_annuler_la_commande_d_un_autre(): void
    {
        $commande = $this->commandeConfirmee($this->autreEtudiant(), ['Poulet Braisé' => 1]);

        $this->enTantQue(self::ETUDIANT)->postJson("/api/commandes/{$commande->id_commande}/annuler")->assertNotFound();
        $this->assertSame('en_attente', $commande->fresh()->statut_commande);
    }

    public function test_le_nombre_de_requetes_ne_depend_pas_du_nombre_de_commandes(): void
    {
        $compter = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->app['auth']->forgetGuards();
            $this->enTantQue(self::ETUDIANT)->getJson('/api/commandes?par_page=50')->assertOk();

            return count(DB::getQueryLog());
        };

        $this->creerCommande(self::ETUDIANT, ['Foléré' => 1]);
        $avant = $compter();
        foreach (range(1, 10) as $i) {
            $this->creerCommande(self::ETUDIANT, ['Foléré' => 1, 'Poulet Braisé' => 1]);
        }

        $this->assertSame($avant, $compter(), 'Problème « N+1 » : une requête par commande.');
    }
}
