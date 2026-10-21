<?php

namespace Tests\Feature\Catalogue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\TestCase;

/** Promotions : réduction en % sur une sélection d'articles, pendant une période. */
class PromotionTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'titre' => 'Semaine du poulet', 'description' => '10 % sur le poulet.', 'taux' => 10,
            'date_debut' => now()->subDay()->toDateString(), 'date_fin' => now()->addDays(6)->toDateString(),
            'articles' => [$this->article('Poulet Braisé')->id_article],
        ], $surcharge);
    }

    public function test_le_gerant_cree_une_promotion_qui_baisse_immediatement_le_prix(): void
    {
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees())
            ->assertCreated()
            ->assertJsonPath('titre', 'Semaine du poulet')
            ->assertJsonPath('statut', 'en_cours')
            ->assertJsonPath('articles.0.nom', 'Poulet Braisé');

        // 2500 − 10 % = 2250, ancien prix conservé pour l'affichage barré.
        $this->getJson('/api/menu-du-jour?q=poulet')
            ->assertJsonPath('data.0.prix', 2250)
            ->assertJsonPath('data.0.prix_normal', 2500)
            ->assertJsonPath('data.0.promotion.taux', 10);
    }

    public function test_le_prix_remise_est_arrondi_au_franc(): void
    {
        // 3500 × (1 − 0,33) = 2345 ; 1500 × (1 − 0,33) = 1005 : pas de centimes en FCFA.
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees([
            'taux' => 33, 'articles' => [$this->article('Poisson Braisé')->id_article],
        ]))->assertCreated();

        $this->getJson('/api/menu-du-jour?q=poisson')->assertJsonPath('data.0.prix', 2345);
    }

    public function test_avec_deux_promotions_la_plus_avantageuse_gagne(): void
    {
        $poulet = $this->article('Poulet Braisé')->id_article;
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees(['taux' => 10, 'articles' => [$poulet]]))->assertCreated();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees(['titre' => 'Flash', 'taux' => 50, 'articles' => [$poulet]]))->assertCreated();

        // Les promotions ne se cumulent pas : on applique la meilleure (−50 %).
        $this->getJson('/api/menu-du-jour?q=poulet')->assertJsonPath('data.0.prix', 1250)->assertJsonPath('data.0.promotion.titre', 'Flash');
    }

    public function test_une_promotion_future_est_a_venir_et_n_agit_pas_encore(): void
    {
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees([
            'date_debut' => now()->addDays(3)->toDateString(), 'date_fin' => now()->addDays(9)->toDateString(),
        ]))->assertCreated()->assertJsonPath('statut', 'a_venir');

        $this->getJson('/api/menu-du-jour?q=poulet')->assertJsonPath('data.0.prix', 2500);
    }

    public function test_la_liste_donne_le_statut_de_chaque_promotion(): void
    {
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees([
            'titre' => 'Ancienne', 'date_debut' => now()->subDays(20)->toDateString(), 'date_fin' => now()->subDays(10)->toDateString(),
        ]))->assertCreated();

        $this->app['auth']->forgetGuards();
        $statuts = collect($this->enTantQue('emma@zeduc.com')->getJson('/api/gestion/promotions')->assertOk()->json())
            ->pluck('statut', 'titre');

        $this->assertSame('terminee', $statuts['Ancienne']);
        $this->assertSame('en_cours', $statuts['Foléré à prix mini']);
    }

    public function test_la_creation_est_validee(): void
    {
        $cas = [
            'tout manque' => [[], ['titre', 'taux', 'date_debut', 'date_fin']],
            'taux à zéro' => [$this->donnees(['taux' => 0]), ['taux']],
            'taux au-dessus de 100' => [$this->donnees(['taux' => 101]), ['taux']],
            'fin avant début' => [$this->donnees(['date_debut' => '2026-12-10', 'date_fin' => '2026-12-01']), ['date_fin']],
            'date mal écrite' => [$this->donnees(['date_debut' => '10/12/2026']), ['date_debut']],
            'article inconnu' => [$this->donnees(['articles' => [99999]]), ['articles.0']],
        ];

        foreach ($cas as $nom => [$donnees, $erreurs]) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $donnees)
                ->assertUnprocessable($nom)->assertJsonValidationErrors($erreurs);
        }
        $this->assertSame(1, DB::table('promotion')->count(), 'Seule la promotion de démo doit exister.');
    }

    public function test_modifier_le_taux_et_remplacer_les_articles(): void
    {
        $creation = $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees())->assertCreated();
        $id = $creation->json('id');
        $burger = $this->article('Burger Étudiant')->id_article;

        $this->app['auth']->forgetGuards();
        $this->enTantQue('emma@zeduc.com')->patchJson("/api/gestion/promotions/{$id}", ['taux' => 20, 'articles' => [$burger]])
            ->assertOk()->assertJsonPath('taux', 20)->assertJsonCount(1, 'articles')->assertJsonPath('articles.0.nom', 'Burger Étudiant');

        // Le poulet n'est plus en promotion, le burger oui (1500 − 20 % = 1200).
        $this->getJson('/api/menu-du-jour?q=poulet')->assertJsonPath('data.0.prix', 2500);
        $this->getJson('/api/menu-du-jour?q=burger')->assertJsonPath('data.0.prix', 1200);
    }

    public function test_une_modification_sans_la_liste_d_articles_ne_la_touche_pas(): void
    {
        $id = $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees())->json('id');

        $this->app['auth']->forgetGuards();
        $this->enTantQue('emma@zeduc.com')->patchJson("/api/gestion/promotions/{$id}", ['titre' => 'Nouveau titre'])
            ->assertOk()->assertJsonPath('titre', 'Nouveau titre')->assertJsonCount(1, 'articles');
    }

    public function test_une_date_de_fin_envoyee_seule_ne_peut_pas_preceder_le_debut_enregistre(): void
    {
        $id = $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees())->json('id');

        $this->app['auth']->forgetGuards();
        $this->enTantQue('emma@zeduc.com')
            ->patchJson("/api/gestion/promotions/{$id}", ['date_fin' => now()->subDays(30)->toDateString()])
            ->assertUnprocessable()->assertJsonValidationErrors('date_fin');
    }

    public function test_l_affiche_est_enregistree(): void
    {
        $reponse = $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/promotions', $this->donnees(['affiche' => $this->fausseImage('affiche.png')]))
            ->assertCreated();

        $this->assertStringContainsString('/storage/promotions/', $reponse->json('affiche_url'));
        Storage::disk('public')->assertExists(DB::table('promotion')->where('titre_promotion', 'Semaine du poulet')->value('affiche_promo'));
    }

    public function test_supprimer_une_promotion_rend_le_prix_normal(): void
    {
        $promo = DB::table('promotion')->value('id_promotion'); // « Foléré à prix mini »

        $this->enTantQue('emma@zeduc.com')->deleteJson("/api/gestion/promotions/{$promo}")->assertNoContent();

        $this->getJson('/api/menu-du-jour?q=foléré')->assertJsonPath('data.0.prix', 1250)->assertJsonPath('data.0.prix_normal', null);
        $this->assertSame(0, DB::table('concerner')->count());
    }

    public function test_une_promotion_utilisee_par_une_commande_ne_peut_pas_etre_supprimee(): void
    {
        $promo = DB::table('promotion')->value('id_promotion');
        $this->commanderArticle($this->article('Foléré')->id_article, $promo);

        $this->enTantQue('emma@zeduc.com')->deleteJson("/api/gestion/promotions/{$promo}")
            ->assertStatus(409)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'déjà été appliquée'));

        $this->assertDatabaseHas('promotion', ['id_promotion' => $promo]);
    }

    public function test_les_promotions_sont_reservees_a_la_direction(): void
    {
        $this->getJson('/api/gestion/promotions')->assertUnauthorized();
        $this->enTantQue('paul@zeduc.com')->getJson('/api/gestion/promotions')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->postJson('/api/gestion/promotions', $this->donnees())->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('admin@zeduc.com')->getJson('/api/gestion/promotions')->assertOk();
    }
}
