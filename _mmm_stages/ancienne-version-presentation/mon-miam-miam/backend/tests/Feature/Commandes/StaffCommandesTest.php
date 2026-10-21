<?php

namespace Tests\Feature\Commandes;

use App\Mail\CommandePreteMail;
use App\Models\Commande;
use App\Services\CommandeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\Concerns\PrepareLesCommandes;
use Tests\TestCase;

/** Tableau « Service en cours » du personnel : les 3 colonnes et les boutons d'action. */
class StaffCommandesTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    private const ETUDIANT = 'etudiant@ucac-icam.com';

    private const PAUL = 'paul@zeduc.com';

    /** Appelle une action du personnel (en repartant d'une authentification propre). */
    private function action(string $action, int $idCommande, string $email = self::PAUL)
    {
        $this->app['auth']->forgetGuards();

        return $this->enTantQue($email)->postJson("/api/staff/commandes/{$idCommande}/{$action}");
    }

    public function test_l_acces_est_reserve_au_personnel(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1]);

        $this->getJson('/api/staff/commandes')->assertUnauthorized();
        $this->enTantQue(self::ETUDIANT)->getJson('/api/staff/commandes')->assertForbidden();
        // L'étudiant ne peut pas non plus utiliser les boutons du personnel.
        $this->action('demarrer', $commande->id_commande, self::ETUDIANT)->assertForbidden();
        $this->assertSame('en_attente', $commande->fresh()->statut_commande);

        foreach (['paul@zeduc.com', 'emma@zeduc.com', 'admin@zeduc.com'] as $email) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue($email)->getJson('/api/staff/commandes')->assertOk();
        }
    }

    public function test_le_tableau_range_les_commandes_par_colonne_de_la_plus_ancienne_a_la_plus_recente(): void
    {
        $premiere = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1]);
        $seconde = $this->commandeConfirmee(self::ETUDIANT, ['Poisson Braisé' => 1], 'livraison');
        $enCuisine = $this->commandeConfirmee(self::ETUDIANT, ['Foléré' => 1]);
        $prete = $this->commandeConfirmee(self::ETUDIANT, ['Cocktail Frais' => 1]);
        $this->commandeConfirmee(self::ETUDIANT, ['Foléré' => 1]); // sera terminée
        $this->creerCommande(self::ETUDIANT, ['Foléré' => 1]);     // non confirmée : invisible

        $service = app(CommandeService::class);
        $paul = $this->compte(self::PAUL);
        $service->demarrer($enCuisine, $paul);
        $service->demarrer($prete, $paul);
        $service->marquerPrete($prete->fresh(), $paul);
        $terminee = Commande::latest('id_commande')->where('statut_commande', 'en_attente')->first();
        $service->annuler($terminee, $paul);

        $tableau = $this->enTantQue(self::PAUL)->getJson('/api/staff/commandes')->assertOk();

        $this->assertSame([$premiere->id_commande, $seconde->id_commande], array_column($tableau->json('a_preparer'), 'id'));
        $this->assertSame([$enCuisine->id_commande], array_column($tableau->json('en_cuisine'), 'id'));
        $this->assertSame([$prete->id_commande], array_column($tableau->json('pret'), 'id'));
    }

    public function test_chaque_carte_contient_le_client_les_plats_le_commentaire_et_l_attente(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 2, 'Foléré' => 1], 'livraison', ['commentaire' => 'Beaucoup de piment SVP !']);
        $commande->update(['date_commande' => now()->subMinutes(3)]);

        $this->enTantQue(self::PAUL)->getJson('/api/staff/commandes')->assertOk()
            ->assertJsonPath('a_preparer.0.client.nom', 'Dan Loubove')
            ->assertJsonPath('a_preparer.0.mode_service', 'livraison')
            ->assertJsonPath('a_preparer.0.commentaire', 'Beaucoup de piment SVP !')
            ->assertJsonPath('a_preparer.0.lignes.0.nom', 'Poulet Braisé')
            ->assertJsonPath('a_preparer.0.lignes.0.quantite', 2)
            ->assertJsonPath('a_preparer.0.depuis_minutes', 3);
    }

    public function test_parcours_complet_sur_place(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1], 'sur_place');
        $id = $commande->id_commande;

        $this->action('demarrer', $id)->assertOk()->assertJsonPath('statut', 'en_preparation');
        $enregistre = $commande->fresh();
        // Le membre du personnel qui lance la préparation est enregistré comme « valideur ».
        $this->assertSame($this->compte(self::PAUL)->id_utilisateur, $enregistre->id_validateur);
        $this->assertNotNull($enregistre->date_validation);

        $this->action('prete', $id)->assertOk()->assertJsonPath('statut', 'prete');
        $this->action('cloturer', $id)->assertOk()->assertJsonPath('statut', 'servie');

        // Le journal garde chaque étape et son auteur.
        $journal = DB::table('historique_commande')->where('id_commande', $id)->orderBy('id_historique')->get();
        $this->assertSame(['panier', 'en_attente', 'en_preparation', 'prete', 'servie'], $journal->pluck('statut_commande')->all());
        $this->assertSame($this->compte(self::PAUL)->id_utilisateur, $journal->last()->id_acteur);
    }

    public function test_une_commande_en_livraison_se_cloture_en_livree(): void
    {
        $id = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1], 'livraison')->id_commande;

        $this->action('demarrer', $id)->assertOk();
        $this->action('prete', $id)->assertOk();
        $this->action('cloturer', $id)->assertOk()->assertJsonPath('statut', 'livree')->assertJsonPath('statut_libelle', 'Livrée');
    }

    public function test_les_passages_de_statut_interdits_sont_refuses(): void
    {
        $id = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1])->id_commande;

        // On ne peut pas sauter d'étape.
        $this->action('prete', $id)->assertStatus(409)->assertJsonPath('message', 'Action impossible : la commande est « En attente ».');
        $this->action('cloturer', $id)->assertStatus(409);

        $this->action('demarrer', $id)->assertOk();
        // Un double clic ne doit pas refaire l'action.
        $this->action('demarrer', $id)->assertStatus(409);
        $this->action('cloturer', $id)->assertStatus(409);

        $this->action('prete', $id)->assertOk();
        $this->action('cloturer', $id)->assertOk();
        // Une commande terminée ne bouge plus.
        foreach (['demarrer', 'prete', 'cloturer', 'annuler'] as $action) {
            $this->action($action, $id)->assertStatus(409);
        }
        $this->assertSame('servie', Commande::find($id)->statut_commande);
    }

    public function test_une_commande_non_confirmee_ne_peut_pas_etre_lancee_en_cuisine(): void
    {
        $id = $this->creerCommande(self::ETUDIANT, ['Poulet Braisé' => 1])->id_commande; // « panier » : pas encore payée

        $this->action('demarrer', $id)->assertStatus(409);
        $this->assertSame('panier', Commande::find($id)->statut_commande);
    }

    public function test_le_personnel_peut_annuler_en_cuisine_et_les_points_sont_rendus(): void
    {
        $commande = $this->commandeConfirmee(self::ETUDIANT, ['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]);
        $this->action('demarrer', $commande->id_commande)->assertOk();
        $this->assertSame(0, $this->solde(self::ETUDIANT));

        $this->action('annuler', $commande->id_commande)->assertOk()->assertJsonPath('statut', 'annulee');

        $this->assertSame(45, $this->solde(self::ETUDIANT));
        $this->assertSame(0, Commande::whereIn('statut_commande', Commande::STATUTS_EN_COURS)->count());
    }

    public function test_notifier_envoie_un_email_au_client_quand_la_commande_est_prete(): void
    {
        Mail::fake();
        $id = $this->commandeConfirmee(self::ETUDIANT, ['Poulet Braisé' => 1])->id_commande;

        // Pas prête : refus.
        $this->action('notifier', $id)->assertStatus(409);
        Mail::assertNothingSent();

        $this->action('demarrer', $id)->assertOk();
        $this->action('prete', $id)->assertOk();
        $this->action('notifier', $id)->assertStatus(202)->assertJsonPath('message', 'Le client a été notifié.');

        Mail::assertSent(CommandePreteMail::class, fn ($mail) => $mail->hasTo('etudiant@ucac-icam.com') && $mail->commande->id_commande === $id);
    }

    public function test_une_commande_inexistante_renvoie_404(): void
    {
        $this->action('demarrer', 99999)->assertNotFound();
    }
}
