<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Jeu de données de démonstration (développement uniquement).
 * Mot de passe de tous les comptes : Demo1234
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Demo1234';

    public function run(): void
    {
        $restaurant = DB::table('restaurant')->value('id_restaurant');
        $roles = DB::table('role')->pluck('id_role', 'libelle_role');

        // --- Étudiants de démonstration ----------------------------------
        // Le seeder est relançable : il retrouve les comptes par email et met à jour leur profil.
        $dan = $this->utilisateur('Dan Loubove', 'etudiant@ucac-icam.com', '699 12 34 56');
        $nathan = $this->utilisateur('Nathan Bella', 'nathan@ucac-icam.com', '699 12 34 57');
        $abdul = $this->utilisateur('Abdul Kadir', 'abdul@ucac-icam.com', '699 12 34 58');

        $this->profilEtudiant($dan, 'ZEDUC-DAN01', 45, null, null);
        $this->profilEtudiant($nathan, 'ZEDUC-NAT01', 0, null, null);
        $pointsParrainage = (int) (DB::table('parametre')->where('id_restaurant', $restaurant)
            ->where('cle_parametre', 'points_parrainage')->value('valeur_parametre') ?? 10);
        $this->profilEtudiant($abdul, 'ZEDUC-ABD01', 0, $dan, $pointsParrainage);

        // --- Personnel de démonstration ----------------------------------
        // Le schéma comporte quatre rôles : le rôle « propriétaire » n'existe pas.
        // Junior Mbongue représente donc le propriétaire avec le rôle administrateur,
        // associé au restaurant ZEDUCSP@CE.
        $admin = $this->utilisateur('Junior Mbongue', 'admin@zeduc.com', '+237 600 00 00 00');
        $this->profilPersonnel($admin, 'ADM-001', $roles['administrateur'], $restaurant, null, '2026-01-05');

        $gerante = $this->utilisateur('EMMA', 'emma@zeduc.com', '+237 600 00 00 01');
        $this->profilPersonnel($gerante, 'GER-101', $roles['gerant'], $restaurant, $admin, '2026-02-01');

        foreach ([
            ['CHRISTE', 'christe@zeduc.com', '+237 600 00 02', 'EMP-101'],
            ['Paul Zogo', 'paul@zeduc.com', '+237 600 00 03', 'EMP-102'],
            ['Mireille Ekotto', 'mireille@zeduc.com', '+237 600 00 04', 'EMP-103'],
        ] as [$nom, $email, $tel, $matricule]) {
            $employe = $this->utilisateur($nom, $email, $tel);
            $this->profilPersonnel($employe, $matricule, $roles['employe'], $restaurant, $gerante, '2026-02-01');
        }

        // --- Articles (reprise du menu du prototype) -----------------------
        // [nom, prix FCFA, catégorie, description, disponible, photo (dans database/seeders/images)]
        $categories = DB::table('categorie')->pluck('id_categorie', 'libelle_categorie');
        $articles = [
            ['Poisson Braisé', 3500, 'plat', 'Poisson braisé, bâtons de manioc et sauce pimentée.', true, 'poisson.jpg'],
            ['Poulet Braisé', 2500, 'plat', 'Poulet braisé mariné aux épices maison.', true, 'poulet.jpg'],
            ['Boulettes de viande', 1500, 'plat', 'Boulettes de viande en sauce crémeuse.', false, 'boulettes.jpg'],
            ['Ndolé Viande', 3000, 'plat', 'Ndolé traditionnel aux arachides, accompagné de viande.', true, 'ndole.jpg'],
            ['Burger Étudiant', 1500, 'snack', 'Burger maison, frites croustillantes.', true, 'burger.jpg'],
            ['Pizza La Terrasse', 3000, 'snack', 'Pizza généreuse cuite à la commande.', true, 'pizza.jpg'],
            ['Foléré', 1250, 'boisson', "Jus d'hibiscus frais, citron vert et épices.", true, 'folere.jpg'],
            ['Cocktail Frais', 1000, 'boisson', 'Cocktail de fruits sans alcool.', true, 'cocktail.jpg'],
            ['Jus de gingembre', 500, 'boisson', 'Gingembre pressé, citron et miel.', false, 'jus-orange.jpg'],
        ];
        $ids = [];
        foreach ($articles as [$nom, $prix, $categorie, $description, $disponible, $photo]) {
            // Photo : copiée sur le disque « public » (accessible via /storage après « php artisan storage:link »).
            $chemin = null;
            if ($photo) {
                $chemin = 'articles/'.$photo;
                Storage::disk('public')->put($chemin, file_get_contents(database_path('seeders/images/'.$photo)));
            }

            // updateOrInsert : crée l'article, ou le met à jour s'il existe déjà (le seeder peut être relancé).
            DB::table('article')->updateOrInsert(
                ['nom_article' => $nom, 'id_restaurant' => $restaurant],
                [
                    'prix_unitaire' => $prix, 'description_article' => $description, 'disponible' => $disponible,
                    'id_categorie' => $categories[$categorie], 'image_article' => $chemin,
                ],
            );
            $ids[$nom] = DB::table('article')->where('nom_article', $nom)->value('id_article');
        }

        // --- Promotion : le Foléré passe de 1250 à 500 FCFA (−60 %) pendant un mois ----
        $promotion = DB::table('promotion')->where('titre_promotion', 'Foléré à prix mini')->value('id_promotion')
            ?? DB::table('promotion')->insertGetId([
                'titre_promotion' => 'Foléré à prix mini',
                'description_promotion' => 'Le jus maison à 500 FCFA au lieu de 1250.',
                'taux_reduction' => 60,
                'date_debut_promo' => now()->subDays(7)->toDateString(),
                'date_fin_promo' => now()->addDays(30)->toDateString(),
            ], 'id_promotion');
        DB::table('concerner')->insertOrIgnore(['id_promotion' => $promotion, 'id_article' => $ids['Foléré']]);

        // --- Menu du jour : TOUS les articles (ceux en rupture restent visibles, marqués « Rupture »)
        $menu = DB::table('menu_jour')->where(['id_restaurant' => $restaurant, 'date_menu' => now()->toDateString()])->value('id_menu_jour')
            ?? DB::table('menu_jour')->insertGetId(['id_restaurant' => $restaurant, 'date_menu' => now()->toDateString()], 'id_menu_jour');
        foreach ($ids as $idArticle) {
            DB::table('composer')->insertOrIgnore(['id_menu_jour' => $menu, 'id_article' => $idArticle]);
        }

        // --- Événements -----------------------------------------------------
        foreach ([
            ['Match Champions League', 'Soirée Match sur grand écran : on diffuse le match en direct.', now()->setTime(20, 0), 0],
            ['Tournoi de Billard', 'Affrontez vos potes. Le gagnant repart avec des points bonus et une conso offerte.', now()->next('Friday')->setTime(16, 0), 5],
        ] as [$titre, $description, $debut, $points]) {
            if (! DB::table('evenement')->where('titre_evenement', $titre)->exists()) {
                DB::table('evenement')->insert([
                    'titre_evenement' => $titre, 'description_evenement' => $description,
                    'date_debut_evenement' => $debut, 'date_fin_evenement' => $debut->copy()->addHours(4),
                    'points_recompense' => $points,
                ]);
            }
        }
    }

    private function utilisateur(string $nom, string $email, string $telephone): int
    {
        DB::table('utilisateur')->updateOrInsert(
            ['email' => $email],
            [
                'nom' => $nom,
                'telephone' => $telephone,
                'mot_de_passe' => Hash::make(self::PASSWORD),
                'statut_compte' => 'actif',
                'email_verified_at' => now(),
            ],
        );

        return (int) DB::table('utilisateur')->where('email', $email)->value('id_utilisateur');
    }

    private function profilEtudiant(int $id, string $code, int $solde, ?int $parrain, ?int $pointsParrainage): void
    {
        DB::table('etudiant')->updateOrInsert(
            ['id_utilisateur' => $id],
            [
                'code_parrainage' => $code,
                'solde_points' => $solde,
                'localisation' => 'Campus UCAC-ICAM (Yansoki)',
                'id_parrain' => $parrain,
                'date_parrainage' => $parrain ? now() : null,
                'recompense_attribuee' => false,
                'points_parrainage' => $pointsParrainage,
            ],
        );
    }

    private function profilPersonnel(int $id, string $matricule, int $role, ?int $restaurant, ?int $createur, string $dateEmbauche): void
    {
        DB::table('personnel')->updateOrInsert(
            ['id_utilisateur' => $id],
            [
                'matricule' => $matricule,
                'date_embauche' => $dateEmbauche,
                'id_role' => $role,
                'id_restaurant' => $restaurant,
                'id_createur' => $createur,
            ],
        );
    }
}
