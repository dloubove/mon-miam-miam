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

/** Devis (prix exact) et création d'une commande par l'étudiant. */
class CreationCommandeTest extends TestCase
{
    use GereLesConnexions, PrepareLeCatalogue, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    private const ETUDIANT = 'etudiant@ucac-icam.com'; // 45 points

    // -------------------------------------------------------------- devis

    public function test_le_devis_calcule_les_prix_du_jour_promotion_comprise_sans_rien_enregistrer(): void
    {
        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes/devis', $this->donneesCommande(['Poisson Braisé' => 1, 'Foléré' => 2]))
            ->assertOk()
            ->assertJsonPath('sous_total', 4500)           // 3500 + 2 × 500 (le Foléré est à 500, pas 1250)
            ->assertJsonPath('lignes.1.prix_unitaire', 500)
            ->assertJsonPath('lignes.1.total', 1000)
            ->assertJsonPath('reduction_points', 0)
            ->assertJsonPath('total', 4500)
            ->assertJsonPath('points_a_gagner', 4)         // 1 point par 1000 FCFA payés
            ->assertJsonPath('solde_points', 45);

        $this->assertSame(0, Commande::count(), 'Un devis ne doit rien enregistrer.');
    }

    public function test_le_devis_utilise_le_maximum_de_points_possible(): void
    {
        // 45 points = 3 tranches de 15 = 3000 FCFA de remise sur 3500.
        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes/devis', $this->donneesCommande(['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]))
            ->assertOk()
            ->assertJsonPath('points_utilises', 45)
            ->assertJsonPath('reduction_points', 3000)
            ->assertJsonPath('total', 500)
            ->assertJsonPath('points_a_gagner', 0); // 500 FCFA : pas encore 1000
    }

    public function test_la_reduction_ne_depasse_jamais_le_montant_de_la_commande(): void
    {
        // Commande de 1500 FCFA : 1 seule tranche (1000 FCFA) est utilisable, même avec 45 points.
        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes/devis', $this->donneesCommande(['Burger Étudiant' => 1], 'sur_place', ['utiliser_points' => true]))
            ->assertJsonPath('points_utilises', 15)
            ->assertJsonPath('reduction_points', 1000)
            ->assertJsonPath('total', 500);
    }

    public function test_une_commande_peut_etre_entierement_couverte_par_les_points(): void
    {
        // 3000 FCFA avec 45 points : remise de 3000, total 0.
        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes/devis', $this->donneesCommande(['Pizza La Terrasse' => 1], 'sur_place', ['utiliser_points' => true]))
            ->assertJsonPath('reduction_points', 3000)->assertJsonPath('total', 0);
    }

    public function test_sous_le_seuil_de_points_aucune_reduction(): void
    {
        DB::table('etudiant')->update(['solde_points' => 14]); // moins d'une tranche (15)

        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes/devis', $this->donneesCommande(['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]))
            ->assertJsonPath('points_utilises', 0)->assertJsonPath('reduction_points', 0)->assertJsonPath('total', 3500);
    }

    public function test_un_seuil_plus_eleve_que_la_tranche_est_respecte(): void
    {
        // L'administrateur relève le seuil à 30 points : avec 15 points on ne peut rien utiliser.
        DB::table('parametre')->where('cle_parametre', 'seuil_min_points_utilisation')->update(['valeur_parametre' => '30']);
        DB::table('etudiant')->update(['solde_points' => 15]);

        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes/devis', $this->donneesCommande(['Poisson Braisé' => 1], 'sur_place', ['utiliser_points' => true]))
            ->assertJsonPath('points_utilises', 0);
    }

    // ----------------------------------------------------------- création

    public function test_la_commande_est_creee_en_attente_de_paiement_avec_des_prix_figes(): void
    {
        $reponse = $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes', $this->donneesCommande(['Poulet Braisé' => 2, 'Foléré' => 1], 'sur_place', ['commentaire' => 'Beaucoup de sauce']))
            ->assertCreated()
            ->assertJsonPath('statut', 'panier')
            ->assertJsonPath('statut_libelle', 'En attente de paiement')
            ->assertJsonPath('mode_service', 'sur_place')
            ->assertJsonPath('heure_arrivee', '12:30')
            ->assertJsonPath('commentaire', 'Beaucoup de sauce')
            ->assertJsonPath('sous_total', 5500)
            ->assertJsonPath('total', 5500)
            ->assertJsonCount(2, 'lignes')
            ->assertJsonPath('lignes.0.nom', 'Poulet Braisé')
            ->assertJsonPath('lignes.0.prix_unitaire', 2500)
            ->assertJsonPath('lignes.0.total', 5000);

        // Le journal contient la création.
        $this->assertSame(['panier'], DB::table('historique_commande')->where('id_commande', $reponse->json('id'))->pluck('statut_commande')->all());
        // Aucun point n'est débité à la création.
        $this->assertSame(45, $this->solde(self::ETUDIANT));
    }

    public function test_le_prix_d_une_ligne_est_fige_meme_si_l_article_change_de_prix(): void
    {
        $id = $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $this->donneesCommande(['Poulet Braisé' => 1]))->json('id');

        // Le restaurant augmente le poulet APRÈS la commande.
        Article::where('nom_article', 'Poulet Braisé')->update(['prix_unitaire' => 9000]);

        $this->app['auth']->forgetGuards();
        $this->enTantQue(self::ETUDIANT)->getJson("/api/commandes/{$id}")
            ->assertJsonPath('lignes.0.prix_unitaire', 2500)->assertJsonPath('total', 2500);
    }

    public function test_en_livraison_l_adresse_par_defaut_est_le_lieu_habituel_de_l_etudiant(): void
    {
        $donnees = $this->donneesCommande(['Poulet Braisé' => 1], 'livraison', ['adresse_livraison' => null]);

        $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $donnees)
            ->assertCreated()->assertJsonPath('adresse_livraison', 'Campus UCAC-ICAM (Yansoki)')->assertJsonPath('heure_arrivee', null);
    }

    public function test_les_champs_sensibles_envoyes_par_le_client_sont_ignores(): void
    {
        // Un pirate tente d'imposer un statut, un montant, un prix et un valideur.
        $donnees = $this->donneesCommande(['Poulet Braisé' => 1], 'sur_place', [
            'statut_commande' => 'livree', 'montant_total' => 1, 'points_gagnes' => 999, 'id_validateur' => 1, 'prix' => 1,
        ]);
        $donnees['lignes'][0]['prix_applique'] = 1;

        $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $donnees)
            ->assertCreated()
            ->assertJsonPath('statut', 'panier')
            ->assertJsonPath('total', 2500)
            ->assertJsonPath('points_gagnes', 0)
            ->assertJsonPath('lignes.0.prix_unitaire', 2500);
    }

    // --------------------------------------------------------- validation

    public function test_la_commande_est_validee(): void
    {
        $poulet = Article::where('nom_article', 'Poulet Braisé')->value('id_article');
        $valide = $this->donneesCommande(['Poulet Braisé' => 1]);

        $cas = [
            'panier vide' => [['lignes' => []] + $valide, ['lignes']],
            'sans lignes' => [array_diff_key($valide, ['lignes' => 1]), ['lignes']],
            'quantité zéro' => [['lignes' => [['article_id' => $poulet, 'quantite' => 0]]] + $valide, ['lignes.0.quantite']],
            'quantité trop grande' => [['lignes' => [['article_id' => $poulet, 'quantite' => 21]]] + $valide, ['lignes.0.quantite']],
            'article en double' => [['lignes' => [['article_id' => $poulet, 'quantite' => 1], ['article_id' => $poulet, 'quantite' => 2]]] + $valide, ['lignes.0.article_id']],
            'mode inconnu' => [['mode_service' => 'drone'] + $valide, ['mode_service']],
            'sur place sans heure' => [['heure_arrivee' => null] + $valide, ['heure_arrivee']],
            'heure mal écrite' => [['heure_arrivee' => '12h30'] + $valide, ['heure_arrivee']],
            'commentaire trop long' => [['commentaire' => str_repeat('a', 501)] + $valide, ['commentaire']],
        ];

        foreach ($cas as $nom => [$donnees, $erreurs]) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $donnees)
                ->assertUnprocessable($nom)->assertJsonValidationErrors($erreurs);
        }
        $this->assertSame(0, Commande::count());
    }

    public function test_la_livraison_exige_une_adresse_quand_l_etudiant_n_en_a_pas_dans_son_profil(): void
    {
        DB::table('etudiant')->update(['localisation' => null]);

        $this->enTantQue(self::ETUDIANT)
            ->postJson('/api/commandes', $this->donneesCommande(['Poulet Braisé' => 1], 'livraison', ['adresse_livraison' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('adresse_livraison');
    }

    public function test_un_article_en_rupture_n_est_pas_commandable(): void
    {
        $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $this->donneesCommande(['Boulettes de viande' => 1]))
            ->assertUnprocessable()
            ->assertJsonPath('errors', fn ($e) => str_contains($e['lignes.0.article_id'][0], 'rupture de stock'));
    }

    public function test_un_article_hors_du_menu_du_jour_n_est_pas_commandable(): void
    {
        $this->creerArticle('Dessert caché', 800, 'dessert');

        $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $this->donneesCommande(['Dessert caché' => 1]))
            ->assertUnprocessable()
            ->assertJsonPath('errors', fn ($e) => str_contains($e['lignes.0.article_id'][0], "pas au menu d'aujourd'hui"));
    }

    public function test_un_article_inexistant_est_refuse_et_rien_n_est_cree(): void
    {
        $donnees = $this->donneesCommande(['Poulet Braisé' => 1]);
        $donnees['lignes'][] = ['article_id' => 99999, 'quantite' => 1];

        $this->enTantQue(self::ETUDIANT)->postJson('/api/commandes', $donnees)
            ->assertUnprocessable()->assertJsonValidationErrors('lignes.1.article_id');
        $this->assertSame(0, Commande::count(), 'Aucune commande partielle ne doit être créée.');
    }

    public function test_seuls_les_etudiants_peuvent_commander(): void
    {
        $donnees = $this->donneesCommande(['Poulet Braisé' => 1]);

        $this->postJson('/api/commandes', $donnees)->assertUnauthorized();
        foreach (['paul@zeduc.com', 'emma@zeduc.com', 'admin@zeduc.com'] as $email) {
            $this->app['auth']->forgetGuards();
            $this->enTantQue($email)->postJson('/api/commandes', $donnees)->assertForbidden();
            $this->app['auth']->forgetGuards();
            $this->enTantQue($email)->postJson('/api/commandes/devis', $donnees)->assertForbidden();
        }
        $this->assertSame(0, Commande::count());
    }
}
