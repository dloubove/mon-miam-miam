<?php

namespace Tests\Feature\Catalogue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\TestCase;

/** « Le Grand Catalogue » : création, modification, suppression des articles par la direction. */
class GestionArticleTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Les photos de test sont écrites dans un disque virtuel : aucun fichier réel n'est créé.
        Storage::fake('public');
    }

    private function idCategorie(string $libelle): int
    {
        return DB::table('categorie')->where('libelle_categorie', $libelle)->value('id_categorie');
    }

    private function donneesValides(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Poulet DG Maison', 'description' => 'Poulet, plantains et légumes.',
            'prix' => 2500, 'categorie_id' => $this->idCategorie('plat'),
        ], $surcharge);
    }

    public function test_le_gerant_cree_un_article_avec_sa_photo(): void
    {
        $reponse = $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/articles', $this->donneesValides([
            'image' => $this->fausseImage('dg.jpg'),
        ]));

        $reponse->assertCreated()
            ->assertJsonPath('nom', 'Poulet DG Maison')
            ->assertJsonPath('prix', 2500)
            ->assertJsonPath('categorie.libelle', 'plat')
            ->assertJsonPath('disponible', true);

        $chemin = DB::table('article')->where('nom_article', 'Poulet DG Maison')->value('image_article');
        $this->assertStringStartsWith('articles/', $chemin);
        Storage::disk('public')->assertExists($chemin);
        $this->assertStringContainsString("/storage/{$chemin}", $reponse->json('image_url'));
        // L'article est rattaché au restaurant.
        $this->assertNotNull(DB::table('article')->where('nom_article', 'Poulet DG Maison')->value('id_restaurant'));
    }

    public function test_on_peut_creer_un_article_sans_photo(): void
    {
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/articles', $this->donneesValides())
            ->assertCreated()->assertJsonPath('image_url', null);
    }

    public function test_un_article_cree_n_est_pas_automatiquement_au_menu_du_jour(): void
    {
        $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/articles', $this->donneesValides())->assertCreated();

        $this->assertNotContains('Poulet DG Maison', $this->noms($this->getJson('/api/menu-du-jour')));
        $this->app['auth']->forgetGuards();
        $catalogue = $this->enTantQue('emma@zeduc.com')->getJson('/api/gestion/articles?q=DG')->assertOk();
        $this->assertFalse($catalogue->json('data.0.dans_menu'));
    }

    public function test_la_creation_est_validee(): void
    {
        $cas = [
            'tout manque' => [[], ['nom', 'prix', 'categorie_id']],
            'prix à zéro' => [$this->donneesValides(['prix' => 0]), ['prix']],
            'prix négatif' => [$this->donneesValides(['prix' => -500]), ['prix']],
            'prix décimal' => [$this->donneesValides(['prix' => 12.5]), ['prix']],
            'catégorie inconnue' => [$this->donneesValides(['categorie_id' => 999]), ['categorie_id']],
            'nom déjà pris' => [$this->donneesValides(['nom' => 'Poulet Braisé']), ['nom']],
            'fichier non image' => [$this->donneesValides(['image' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')]), ['image']],
            'image trop lourde' => [$this->donneesValides(['image' => $this->fausseImage('gros.jpg', 3000)]), ['image']],
        ];

        foreach ($cas as $nom => [$donnees, $erreurs]) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue('emma@zeduc.com')->postJson('/api/gestion/articles', $donnees)
                ->assertUnprocessable($nom)->assertJsonValidationErrors($erreurs);
        }
        $this->assertSame(9, DB::table('article')->count(), 'Aucun article ne devait être créé.');
    }

    public function test_l_employe_et_l_etudiant_ne_peuvent_pas_creer_d_article(): void
    {
        $this->postJson('/api/gestion/articles', $this->donneesValides())->assertUnauthorized();
        $this->enTantQue('paul@zeduc.com')->postJson('/api/gestion/articles', $this->donneesValides())->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->postJson('/api/gestion/articles', $this->donneesValides())->assertForbidden();
    }

    public function test_modification_partielle_seuls_les_champs_envoyes_changent(): void
    {
        $poulet = $this->article('Poulet Braisé');

        $this->enTantQue('emma@zeduc.com')->patchJson("/api/gestion/articles/{$poulet->id_article}", ['prix' => 2800])
            ->assertOk()->assertJsonPath('prix', 2800)->assertJsonPath('nom', 'Poulet Braisé');

        $poulet->refresh();
        $this->assertSame(2800, $poulet->prix_unitaire);
        $this->assertSame('Poulet braisé mariné aux épices maison.', $poulet->description_article);
    }

    public function test_un_article_peut_garder_son_nom_mais_pas_prendre_celui_d_un_autre(): void
    {
        $poulet = $this->article('Poulet Braisé');

        $this->enTantQue('emma@zeduc.com')->patchJson("/api/gestion/articles/{$poulet->id_article}", ['nom' => 'Poulet Braisé', 'prix' => 2600])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('emma@zeduc.com')->patchJson("/api/gestion/articles/{$poulet->id_article}", ['nom' => 'Poisson Braisé'])
            ->assertUnprocessable()->assertJsonValidationErrors('nom');
    }

    public function test_changer_la_photo_supprime_l_ancienne(): void
    {
        $poulet = $this->article('Poulet Braisé');
        $ancien = 'articles/ancienne.jpg';
        Storage::disk('public')->put($ancien, 'x');
        $poulet->update(['image_article' => $ancien]);

        // Formulaire avec fichier : le navigateur envoie POST + « _method=PATCH » (PHP ne lit pas les fichiers d'un vrai PATCH).
        $this->enTantQue('emma@zeduc.com')
            ->post("/api/gestion/articles/{$poulet->id_article}", ['_method' => 'PATCH', 'image' => $this->fausseImage('nouvelle.png')], ['Accept' => 'application/json'])
            ->assertOk();

        $nouveau = $poulet->fresh()->image_article;
        $this->assertNotSame($ancien, $nouveau);
        Storage::disk('public')->assertMissing($ancien);
        Storage::disk('public')->assertExists($nouveau);
    }

    public function test_on_peut_retirer_la_photo(): void
    {
        $poulet = $this->article('Poulet Braisé');
        $poulet->update(['image_article' => 'articles/a-retirer.jpg']);
        Storage::disk('public')->put('articles/a-retirer.jpg', 'x');

        $this->enTantQue('emma@zeduc.com')->patchJson("/api/gestion/articles/{$poulet->id_article}", ['supprimer_image' => true])
            ->assertOk()->assertJsonPath('image_url', null);

        Storage::disk('public')->assertMissing('articles/a-retirer.jpg');
    }

    public function test_le_catalogue_complet_est_pagine_et_filtrable(): void
    {
        $this->creerArticle('Dessert du chef', 900, 'dessert');

        $this->enTantQue('emma@zeduc.com')->getJson('/api/gestion/articles?par_page=5')
            ->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.total', 10);

        $this->app['auth']->forgetGuards();
        $this->assertSame(['Dessert du chef'], $this->noms($this->enTantQue('emma@zeduc.com')->getJson('/api/gestion/articles?categorie=dessert')));
    }

    public function test_le_gerant_ne_peut_pas_supprimer_mais_l_administrateur_oui(): void
    {
        $id = $this->creerArticle('À supprimer', 700);
        Storage::disk('public')->put('articles/a-supprimer.jpg', 'x');
        DB::table('article')->where('id_article', $id)->update(['image_article' => 'articles/a-supprimer.jpg']);

        // Le gérant a le droit de modifier, pas de supprimer (cf. maquette : icône cadenas).
        $this->enTantQue('emma@zeduc.com')->deleteJson("/api/gestion/articles/{$id}")->assertForbidden();
        $this->assertDatabaseHas('article', ['id_article' => $id]);

        $this->app['auth']->forgetGuards();
        $this->enTantQue('admin@zeduc.com')->deleteJson("/api/gestion/articles/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('article', ['id_article' => $id]);
        Storage::disk('public')->assertMissing('articles/a-supprimer.jpg');
    }

    public function test_supprimer_un_article_le_retire_aussi_des_menus(): void
    {
        $poulet = $this->article('Poulet Braisé');

        $this->enTantQue('admin@zeduc.com')->deleteJson("/api/gestion/articles/{$poulet->id_article}")->assertNoContent();

        $this->assertNotContains('Poulet Braisé', $this->noms($this->getJson('/api/menu-du-jour')));
        $this->assertSame(0, DB::table('composer')->where('id_article', $poulet->id_article)->count());
    }

    public function test_un_article_deja_commande_ne_peut_pas_etre_supprime(): void
    {
        $poulet = $this->article('Poulet Braisé');
        $this->commanderArticle($poulet->id_article);

        $this->enTantQue('admin@zeduc.com')->deleteJson("/api/gestion/articles/{$poulet->id_article}")
            ->assertStatus(409)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'déjà été commandé'));

        $this->assertDatabaseHas('article', ['id_article' => $poulet->id_article]);
    }

    public function test_un_article_inexistant_renvoie_404(): void
    {
        $this->enTantQue('admin@zeduc.com')->deleteJson('/api/gestion/articles/99999')->assertNotFound();
    }
}
