<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Étape 1 : le schéma de la base (22 tables) et ses règles d'intégrité.
 */
class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private int $etudiant;

    private int $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etudiant = DB::table('utilisateur')->where('email', 'etudiant@ucac-icam.com')->value('id_utilisateur');
        $this->restaurant = DB::table('restaurant')->value('id_restaurant');
    }

    /** Vérifie que la base REFUSE l'opération (sans casser la transaction du test). */
    private function assertRejected(Closure $operation, string $message = ''): void
    {
        try {
            DB::transaction($operation);
        } catch (QueryException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('La base aurait dû refuser cette opération. '.$message);
    }

    private function commande(array $override = []): array
    {
        return array_merge([
            'mode_service' => 'sur_place', 'heure_arrivee' => '12:30:00',
            'id_etudiant' => $this->etudiant, 'id_restaurant' => $this->restaurant,
        ], $override);
    }

    public function test_les_22_tables_du_modele_existent(): void
    {
        $tables = ['utilisateur', 'etudiant', 'personnel', 'role', 'restaurant', 'parametre', 'categorie', 'article',
            'menu_jour', 'composer', 'promotion', 'commande', 'contenir', 'paiement', 'mouvement_points',
            'reclamation', 'evenement', 'participer', 'mini_jeu', 'jouer', 'jeton_mdp', 'consentement'];

        $this->assertCount(22, $tables);
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table manquante : {$table}");
        }
    }

    public function test_les_donnees_de_reference_sont_chargees(): void
    {
        $this->assertEqualsCanonicalizing(['employe', 'gerant', 'administrateur'], DB::table('role')->pluck('libelle_role')->all());
        $this->assertEqualsCanonicalizing(['plat', 'boisson', 'dessert', 'snack'], DB::table('categorie')->pluck('libelle_categorie')->all());
        $this->assertSame('15', DB::table('parametre')->where('cle_parametre', 'points_par_tranche_reduction')->value('valeur_parametre'));
        $this->assertSame('1000', DB::table('parametre')->where('cle_parametre', 'fcfa_pour_15_points')->value('valeur_parametre'));
        $this->assertSame(2, DB::table('mini_jeu')->count());
    }

    public function test_les_comptes_de_demo_ont_le_bon_role(): void
    {
        $roleDe = fn (string $email) => DB::table('utilisateur')
            ->join('personnel', 'personnel.id_utilisateur', '=', 'utilisateur.id_utilisateur')
            ->join('role', 'role.id_role', '=', 'personnel.id_role')
            ->where('email', $email)->value('libelle_role');

        $this->assertSame('administrateur', $roleDe('admin@zeduc.com'));
        $this->assertSame('gerant', $roleDe('emma@zeduc.com'));
        $this->assertSame('employe', $roleDe('paul@zeduc.com'));
        $this->assertSame(45, DB::table('etudiant')->where('id_utilisateur', $this->etudiant)->value('solde_points'));
    }

    public function test_les_mots_de_passe_sont_haches(): void
    {
        $hash = DB::table('utilisateur')->where('email', 'etudiant@ucac-icam.com')->value('mot_de_passe');
        $this->assertNotSame('Demo1234', $hash);
        $this->assertTrue(password_verify('Demo1234', $hash));
    }

    public function test_le_seeding_est_idempotent(): void
    {
        $this->seed();
        $this->assertSame(8, DB::table('utilisateur')->count());
        $this->assertSame(9, DB::table('article')->count());
        $this->assertSame(3, DB::table('role')->count());
    }

    public function test_un_email_ne_peut_pas_etre_utilise_deux_fois(): void
    {
        $this->assertRejected(fn () => DB::table('utilisateur')->insert([
            'nom' => 'Doublon', 'email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => 'x', 'telephone' => '699000000',
        ]));
    }

    public function test_un_statut_de_commande_inconnu_est_refuse(): void
    {
        $this->assertRejected(fn () => DB::table('commande')->insert($this->commande(['statut_commande' => 'teleportee'])));
        $this->assertSame(0, DB::table('commande')->count());
    }

    public function test_une_commande_sur_place_exige_une_heure_et_une_livraison_une_adresse(): void
    {
        $this->assertRejected(fn () => DB::table('commande')->insert($this->commande(['heure_arrivee' => null])));
        $this->assertRejected(fn () => DB::table('commande')->insert($this->commande(['mode_service' => 'livraison', 'heure_arrivee' => null])));

        DB::table('commande')->insert($this->commande(['mode_service' => 'livraison', 'heure_arrivee' => null, 'adresse_livraison' => 'Résidence Les Mangueries']));
        $this->assertSame(1, DB::table('commande')->count());
    }

    public function test_le_solde_de_points_ne_peut_pas_etre_negatif(): void
    {
        $this->assertRejected(fn () => DB::table('etudiant')->where('id_utilisateur', $this->etudiant)->update(['solde_points' => -1]));
    }

    public function test_un_etudiant_ne_peut_pas_etre_son_propre_parrain(): void
    {
        $this->assertRejected(fn () => DB::table('etudiant')->where('id_utilisateur', $this->etudiant)
            ->update(['id_parrain' => $this->etudiant, 'date_parrainage' => now()]));
    }

    public function test_un_prix_doit_etre_strictement_positif(): void
    {
        $this->assertRejected(fn () => DB::table('article')->where('nom_article', 'Foléré')->update(['prix_unitaire' => 0]));
    }

    public function test_les_mouvements_de_points_respectent_leur_signe(): void
    {
        $commande = DB::table('commande')->insertGetId($this->commande(), 'id_commande');

        // une utilisation doit être négative, un gain positif
        $this->assertRejected(fn () => DB::table('mouvement_points')->insert(
            ['type_mouvement' => 'utilisation', 'nombre_points' => 15, 'id_etudiant' => $this->etudiant, 'id_commande' => $commande]));
        $this->assertRejected(fn () => DB::table('mouvement_points')->insert(
            ['type_mouvement' => 'gain_mini_jeu', 'nombre_points' => -2, 'id_etudiant' => $this->etudiant]));
        // un gain de commande exige une commande
        $this->assertRejected(fn () => DB::table('mouvement_points')->insert(
            ['type_mouvement' => 'gain_commande', 'nombre_points' => 3, 'id_etudiant' => $this->etudiant]));
    }

    public function test_un_seul_gain_de_fidelite_par_commande(): void
    {
        $commande = DB::table('commande')->insertGetId($this->commande(), 'id_commande');
        $gain = ['type_mouvement' => 'gain_commande', 'nombre_points' => 3, 'id_etudiant' => $this->etudiant, 'id_commande' => $commande];

        DB::table('mouvement_points')->insert($gain);
        $this->assertRejected(fn () => DB::table('mouvement_points')->insert($gain), 'Double crédit de fidélité accepté.');
    }

    public function test_on_ne_supprime_pas_un_utilisateur_qui_a_un_profil(): void
    {
        $this->assertRejected(fn () => DB::table('utilisateur')->where('id_utilisateur', $this->etudiant)->delete());
    }

    public function test_une_decision_du_gerant_exige_une_reponse_prealable(): void
    {
        $this->assertRejected(fn () => DB::table('reclamation')->insert([
            'objet' => 'Plat froid', 'description_reclamation' => 'Arrivé froid', 'id_etudiant' => $this->etudiant,
            'decision_gerant' => 'validee', 'date_decision' => now(),
        ]));
    }

    public function test_les_lignes_de_commande_sont_supprimees_avec_leur_commande(): void
    {
        $commande = DB::table('commande')->insertGetId($this->commande(), 'id_commande');
        $article = DB::table('article')->value('id_article');
        DB::table('contenir')->insert(['id_commande' => $commande, 'id_article' => $article, 'quantite' => 2, 'prix_applique' => 2500]);

        $this->assertRejected(fn () => DB::table('contenir')->insert(
            ['id_commande' => $commande, 'id_article' => DB::table('article')->skip(1)->value('id_article'), 'quantite' => 0, 'prix_applique' => 2500]));

        DB::table('commande')->where('id_commande', $commande)->delete();
        $this->assertSame(0, DB::table('contenir')->where('id_commande', $commande)->count());
    }
}
