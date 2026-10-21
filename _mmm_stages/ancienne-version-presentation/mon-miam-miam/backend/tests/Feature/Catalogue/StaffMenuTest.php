<?php

namespace Tests\Feature\Catalogue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\TestCase;

/** Service quotidien : rupture de stock et composition du menu du jour par le personnel. */
class StaffMenuTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, RefreshDatabase;

    protected bool $seed = true;

    public function test_l_acces_est_reserve_au_personnel(): void
    {
        $this->getJson('/api/staff/menu')->assertUnauthorized();

        $this->enTantQue('etudiant@ucac-icam.com')->getJson('/api/staff/menu')->assertForbidden();

        foreach (['paul@zeduc.com', 'emma@zeduc.com', 'admin@zeduc.com'] as $email) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue($email)->getJson('/api/staff/menu')->assertOk();
        }
    }

    public function test_la_page_menu_du_jour_liste_tous_les_articles_avec_leur_presence_au_menu(): void
    {
        $this->creerArticle('Dessert du chef', 900, 'dessert'); // au catalogue, pas au menu

        $reponse = $this->enTantQue('paul@zeduc.com')->getJson('/api/staff/menu')->assertOk();

        $reponse->assertJsonPath('date', now()->toDateString())->assertJsonCount(10, 'articles');

        $parNom = collect($reponse->json('articles'))->keyBy('nom');
        $this->assertTrue($parNom['Poulet Braisé']['dans_menu']);
        $this->assertFalse($parNom['Dessert du chef']['dans_menu']);
        $this->assertFalse($parNom['Boulettes de viande']['disponible']);
    }

    public function test_un_employe_peut_mettre_un_plat_en_rupture_puis_le_remettre(): void
    {
        $poulet = $this->article('Poulet Braisé');

        $this->enTantQue('paul@zeduc.com')
            ->patchJson("/api/staff/articles/{$poulet->id_article}/disponibilite", ['disponible' => false])
            ->assertOk()
            ->assertJsonPath('disponible', false);
        $this->assertFalse($poulet->fresh()->disponible);

        // Visible tout de suite côté public, marqué indisponible.
        $this->assertFalse(collect($this->getJson('/api/menu-du-jour?q=poulet')->json('data'))->first()['disponible']);

        $this->enTantQue('paul@zeduc.com')
            ->patchJson("/api/staff/articles/{$poulet->id_article}/disponibilite", ['disponible' => true])
            ->assertOk()->assertJsonPath('disponible', true);
    }

    public function test_la_disponibilite_exige_un_booleen_et_un_article_existant(): void
    {
        $poulet = $this->article('Poulet Braisé');

        $this->enTantQue('paul@zeduc.com')->patchJson("/api/staff/articles/{$poulet->id_article}/disponibilite", [])
            ->assertUnprocessable()->assertJsonValidationErrors('disponible');
        $this->app['auth']->forgetGuards();
        $this->enTantQue('paul@zeduc.com')->patchJson("/api/staff/articles/{$poulet->id_article}/disponibilite", ['disponible' => 'peut-être'])
            ->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('paul@zeduc.com')->patchJson('/api/staff/articles/99999/disponibilite', ['disponible' => true])
            ->assertNotFound();
    }

    public function test_un_etudiant_ne_peut_pas_modifier_la_disponibilite(): void
    {
        $poulet = $this->article('Poulet Braisé');

        $this->enTantQue('etudiant@ucac-icam.com')
            ->patchJson("/api/staff/articles/{$poulet->id_article}/disponibilite", ['disponible' => false])
            ->assertForbidden();
        $this->assertTrue($poulet->fresh()->disponible);
    }

    public function test_composer_le_menu_remplace_la_selection_du_jour(): void
    {
        $ids = [$this->article('Poulet Braisé')->id_article, $this->article('Cocktail Frais')->id_article];

        $this->enTantQue('paul@zeduc.com')->putJson('/api/staff/menu-du-jour', ['articles' => $ids])
            ->assertOk()
            ->assertJsonPath('date', now()->toDateString());

        // Le public ne voit plus que ces deux articles.
        $this->assertEqualsCanonicalizing(['Poulet Braisé', 'Cocktail Frais'], $this->noms($this->getJson('/api/menu-du-jour')));

        // Le staff voit la même chose via « dans_menu ».
        $this->app['auth']->forgetGuards();
        $enMenu = collect($this->enTantQue('paul@zeduc.com')->getJson('/api/staff/menu')->json('articles'))
            ->where('dans_menu', true)->pluck('nom')->all();
        $this->assertEqualsCanonicalizing(['Poulet Braisé', 'Cocktail Frais'], $enMenu);
    }

    public function test_composer_deux_fois_ne_cree_pas_de_doublon(): void
    {
        $ids = [$this->article('Poulet Braisé')->id_article];

        $this->enTantQue('paul@zeduc.com')->putJson('/api/staff/menu-du-jour', ['articles' => $ids])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('paul@zeduc.com')->putJson('/api/staff/menu-du-jour', ['articles' => $ids])->assertOk();

        $this->assertSame(1, DB::table('menu_jour')->where('date_menu', now()->toDateString())->count());
        $this->assertSame(1, DB::table('composer')->count());
    }

    public function test_on_peut_vider_le_menu_du_jour(): void
    {
        $this->enTantQue('paul@zeduc.com')->putJson('/api/staff/menu-du-jour', ['articles' => []])->assertOk();

        $this->getJson('/api/menu-du-jour')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_on_cree_le_menu_du_jour_s_il_n_existe_pas_encore(): void
    {
        DB::table('menu_jour')->delete(); // menu et liens supprimés (cascade)

        $this->enTantQue('paul@zeduc.com')
            ->putJson('/api/staff/menu-du-jour', ['articles' => [$this->article('Poulet Braisé')->id_article]])->assertOk();

        $this->assertSame(['Poulet Braisé'], $this->noms($this->getJson('/api/menu-du-jour')));
    }

    public function test_la_composition_du_menu_est_validee(): void
    {
        $poulet = $this->article('Poulet Braisé')->id_article;

        $cas = [
            'champ absent' => [[], 'articles'],
            'pas une liste' => [['articles' => 'poulet'], 'articles'],
            'article inconnu' => [['articles' => [99999]], 'articles.0'],
            'doublon' => [['articles' => [$poulet, $poulet]], 'articles.0'],
        ];
        foreach ($cas as [$donnees, $champEnErreur]) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue('paul@zeduc.com')->putJson('/api/staff/menu-du-jour', $donnees)
                ->assertUnprocessable()->assertJsonValidationErrors($champEnErreur);
        }

        // Aucune de ces requêtes refusées n'a modifié le menu.
        $this->assertSame(9, DB::table('composer')->count());
    }

    public function test_un_etudiant_ne_peut_pas_composer_le_menu(): void
    {
        $this->enTantQue('etudiant@ucac-icam.com')->putJson('/api/staff/menu-du-jour', ['articles' => []])->assertForbidden();
        $this->assertSame(9, DB::table('composer')->count());
    }
}
