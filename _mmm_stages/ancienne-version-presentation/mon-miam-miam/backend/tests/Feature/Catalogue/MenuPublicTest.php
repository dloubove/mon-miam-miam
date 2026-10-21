<?php

namespace Tests\Feature\Catalogue;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\PrepareLeCatalogue;
use Tests\TestCase;

/** Menu du jour public : ce que voit un visiteur sur la page d'accueil et la page « Voir plus ». */
class MenuPublicTest extends TestCase
{
    use PrepareLeCatalogue, RefreshDatabase;

    protected bool $seed = true;

    public function test_les_categories_sont_publiques_et_incluent_snack(): void
    {
        $this->getJson('/api/categories')
            ->assertOk()
            // Liste non paginée : tableau JSON direct (seules les listes paginées ont « data », « links », « meta »).
            ->assertJsonCount(4)
            ->assertJsonFragment(['libelle' => 'snack']);
    }

    public function test_le_menu_du_jour_est_accessible_sans_connexion(): void
    {
        $this->getJson('/api/menu-du-jour')
            ->assertOk()
            ->assertJsonCount(9, 'data')
            ->assertJsonStructure(['data' => [['id', 'nom', 'description', 'categorie' => ['id', 'libelle'],
                'prix', 'prix_normal', 'promotion', 'image_url', 'disponible']], 'links', 'meta' => ['total', 'current_page']]);
    }

    public function test_l_onglet_plats_exclut_les_boissons_et_l_onglet_boissons_ne_contient_qu_elles(): void
    {
        $plats = $this->getJson('/api/menu-du-jour?type=plats')->assertOk();
        $this->assertEqualsCanonicalizing(
            ['Poisson Braisé', 'Poulet Braisé', 'Boulettes de viande', 'Ndolé Viande', 'Burger Étudiant', 'Pizza La Terrasse'],
            $this->noms($plats),
        );

        $boissons = $this->getJson('/api/menu-du-jour?type=boissons')->assertOk();
        $this->assertEqualsCanonicalizing(['Foléré', 'Cocktail Frais', 'Jus de gingembre'], $this->noms($boissons));
    }

    public function test_l_accueil_limite_le_nombre_d_articles_avec_par_page(): void
    {
        $this->getJson('/api/menu-du-jour?type=plats&par_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 6)      // 6 plats au total : le bouton « Voir plus » a un sens
            ->assertJsonPath('meta.per_page', 3);

        // Page 2 : les 3 suivants, sans doublon avec la page 1.
        $page1 = $this->noms($this->getJson('/api/menu-du-jour?type=plats&par_page=3&page=1'));
        $page2 = $this->noms($this->getJson('/api/menu-du-jour?type=plats&par_page=3&page=2'));
        $this->assertCount(6, array_unique(array_merge($page1, $page2)));
    }

    public function test_un_article_en_promotion_expose_le_prix_remise_et_l_ancien_prix(): void
    {
        $reponse = $this->getJson('/api/menu-du-jour?q=foléré')->assertOk();

        $reponse->assertJsonPath('data.0.prix', 500)
            ->assertJsonPath('data.0.prix_normal', 1250)
            ->assertJsonPath('data.0.promotion.taux', 60)
            ->assertJsonPath('data.0.promotion.titre', 'Foléré à prix mini');
    }

    public function test_un_article_sans_promotion_n_a_pas_de_prix_normal(): void
    {
        $this->getJson('/api/menu-du-jour?q=poulet')->assertOk()
            ->assertJsonPath('data.0.prix', 2500)
            ->assertJsonPath('data.0.prix_normal', null)
            ->assertJsonPath('data.0.promotion', null);
    }

    public function test_une_promotion_terminee_ou_a_venir_ne_s_applique_pas(): void
    {
        $promo = Promotion::first();

        $promo->update(['date_debut_promo' => now()->subDays(10), 'date_fin_promo' => now()->subDay()]);
        $this->getJson('/api/menu-du-jour?q=foléré')->assertJsonPath('data.0.prix', 1250)->assertJsonPath('data.0.prix_normal', null);

        $promo->update(['date_debut_promo' => now()->addDay(), 'date_fin_promo' => now()->addDays(5)]);
        $this->getJson('/api/menu-du-jour?q=foléré')->assertJsonPath('data.0.prix', 1250);

        // Le dernier jour de la promotion est inclus.
        $promo->update(['date_debut_promo' => now()->subDays(3), 'date_fin_promo' => now()]);
        $this->getJson('/api/menu-du-jour?q=foléré')->assertJsonPath('data.0.prix', 500);
    }

    public function test_un_article_en_rupture_reste_visible_mais_marque_indisponible(): void
    {
        $this->getJson('/api/menu-du-jour?q=boulettes')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.disponible', false);
    }

    public function test_seuls_les_articles_du_menu_d_aujourd_hui_sont_proposes(): void
    {
        $this->creerArticle('Dessert caché', 800, 'dessert'); // existe au catalogue mais pas au menu

        $this->assertNotContains('Dessert caché', $this->noms($this->getJson('/api/menu-du-jour')));

        // Le menu d'hier n'est pas celui d'aujourd'hui : si on déplace la date, la liste devient vide.
        DB::table('menu_jour')->update(['date_menu' => now()->subDay()->toDateString()]);
        $this->getJson('/api/menu-du-jour')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_la_recherche_ignore_la_casse_et_cherche_dans_le_nom_et_la_description(): void
    {
        $this->assertSame(['Boulettes de viande'], $this->noms($this->getJson('/api/menu-du-jour?q=BOULETTE')));
        // « arachides » n'apparaît que dans la description du ndolé.
        $this->assertSame(['Ndolé Viande'], $this->noms($this->getJson('/api/menu-du-jour?q=arachides')));
        $this->assertSame([], $this->noms($this->getJson('/api/menu-du-jour?q=sushi')));
    }

    public function test_les_caracteres_speciaux_de_la_recherche_ne_sont_pas_des_jokers(): void
    {
        // Sans protection, « % » serait un joker SQL qui renverrait tout le catalogue.
        $this->assertSame([], $this->noms($this->getJson('/api/menu-du-jour?q=%25')));
        $this->assertSame([], $this->noms($this->getJson('/api/menu-du-jour?q=_')));
    }

    public function test_filtre_par_categorie_avec_le_nom_ou_l_identifiant(): void
    {
        $this->assertEqualsCanonicalizing(['Burger Étudiant', 'Pizza La Terrasse'], $this->noms($this->getJson('/api/menu-du-jour?categorie=snack')));

        $idSnack = DB::table('categorie')->where('libelle_categorie', 'snack')->value('id_categorie');
        $this->assertEqualsCanonicalizing(['Burger Étudiant', 'Pizza La Terrasse'], $this->noms($this->getJson("/api/menu-du-jour?categorie={$idSnack}")));
    }

    public function test_le_filtre_de_prix_porte_sur_le_prix_remise(): void
    {
        // Le Foléré coûte 1250 normalement mais 500 aujourd'hui : il doit apparaître sous 600 FCFA.
        $this->assertEqualsCanonicalizing(['Foléré', 'Jus de gingembre'], $this->noms($this->getJson('/api/menu-du-jour?prix_max=600')));

        $this->assertEqualsCanonicalizing(
            ['Poisson Braisé', 'Ndolé Viande', 'Pizza La Terrasse'],
            $this->noms($this->getJson('/api/menu-du-jour?prix_min=3000')),
        );

        // Fourchette
        $this->assertEqualsCanonicalizing(['Poulet Braisé'], $this->noms($this->getJson('/api/menu-du-jour?prix_min=2000&prix_max=2999')));
    }

    public function test_filtres_disponibles_et_promotions(): void
    {
        $this->assertCount(7, $this->noms($this->getJson('/api/menu-du-jour?disponible=1')));
        $this->assertCount(7, $this->noms($this->getJson('/api/menu-du-jour?disponible=true')));
        $this->assertSame(['Foléré'], $this->noms($this->getJson('/api/menu-du-jour?promo=1')));
    }

    public function test_les_filtres_se_combinent(): void
    {
        $this->assertSame(
            ['Cocktail Frais'],
            $this->noms($this->getJson('/api/menu-du-jour?type=boissons&disponible=1&prix_min=900&q=cocktail')),
        );
    }

    public function test_les_tris(): void
    {
        $croissant = $this->noms($this->getJson('/api/menu-du-jour?tri=prix_asc'));
        // Le Foléré (1250 normalement, 500 en promo) est classé avec les articles à 500, avant le cocktail à 1000.
        $this->assertLessThan(array_search('Cocktail Frais', $croissant), array_search('Foléré', $croissant));
        $this->assertSame('Poisson Braisé', $this->noms($this->getJson('/api/menu-du-jour?tri=prix_desc'))[0]);

        $alphabetique = $this->noms($this->getJson('/api/menu-du-jour?tri=nom'));
        $this->assertSame('Boulettes de viande', $alphabetique[0]);
        $this->assertSame($alphabetique, collect($alphabetique)->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all());
    }

    public function test_les_parametres_invalides_sont_refuses(): void
    {
        $this->getJson('/api/menu-du-jour?prix_min=2000&prix_max=1000')->assertUnprocessable()->assertJsonValidationErrors('prix_max');
        $this->getJson('/api/menu-du-jour?par_page=51')->assertUnprocessable()->assertJsonValidationErrors('par_page');
        $this->getJson('/api/menu-du-jour?type=desserts')->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->getJson('/api/menu-du-jour?tri=hasard')->assertUnprocessable()->assertJsonValidationErrors('tri');
        $this->getJson('/api/menu-du-jour?prix_min=abc')->assertUnprocessable()->assertJsonValidationErrors('prix_min');
    }

    public function test_le_nombre_de_requetes_sql_ne_depend_pas_du_nombre_d_articles(): void
    {
        $compter = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/menu-du-jour?par_page=50')->assertOk();

            return count(DB::getQueryLog());
        };

        $avant = $compter();

        // On ajoute 20 articles au menu : si le code faisait une requête par article (problème « N+1 »),
        // le compteur augmenterait de 20.
        $menu = DB::table('menu_jour')->value('id_menu_jour');
        for ($i = 1; $i <= 20; $i++) {
            DB::table('composer')->insert(['id_menu_jour' => $menu, 'id_article' => $this->creerArticle("Plat n°{$i}")]);
        }

        $this->assertSame($avant, $compter(), 'Le nombre de requêtes a augmenté avec le nombre d\'articles (N+1).');
    }
}
