<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de référence indispensables au fonctionnement (idempotent, sûr en production) :
 * rôles, catégories, restaurant, règles de fidélité, mini-jeux.
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['employe', 'gerant', 'administrateur'] as $role) {
            DB::table('role')->insertOrIgnore(['libelle_role' => $role]);
        }

        // « snack » (burger, pizza…) a été ajouté à l'étape 3 pour correspondre au frontend.
        foreach (['plat', 'boisson', 'dessert', 'snack'] as $categorie) {
            DB::table('categorie')->insertOrIgnore(['libelle_categorie' => $categorie]);
        }

        $restaurant = DB::table('restaurant')->where('nom_restaurant', 'ZEDUCSP@CE')->value('id_restaurant')
            ?? DB::table('restaurant')->insertGetId([
                'nom_restaurant' => 'ZEDUCSP@CE',
                'adresse_restaurant' => 'Résidence La Terrasse, campus UCAC-ICAM de Yansoki, Douala',
                'telephone_restaurant' => '+237000000000',
            ], 'id_restaurant');

        // Valeurs de fidélité du cahier des charges, modifiables par restaurant.
        $parametres = [
            ['points_par_1000_fcfa', '1', 'Points gagnés par tranche de 1000 FCFA payés'],
            ['points_par_tranche_reduction', '15', 'Nombre de points correspondant à une tranche de remise'],
            ['fcfa_pour_15_points', '1000', 'Réduction en FCFA pour 15 points utilisés'],
            ['seuil_min_points_utilisation', '15', 'Nombre minimal de points utilisables'],
            ['duree_validite_points_mois', '12', 'Durée de validité indicative des points, en mois'],
            ['points_parrainage', '10', 'Points offerts au parrain quand son filleul paie sa première commande'],
        ];
        foreach ($parametres as [$cle, $valeur, $description]) {
            DB::table('parametre')->insertOrIgnore([
                'id_restaurant' => $restaurant,
                'cle_parametre' => $cle,
                'valeur_parametre' => $valeur,
                'description_parametre' => $description,
            ]);
        }

        DB::table('mini_jeu')->insertOrIgnore([
            ['nom_mini_jeu' => 'Quiz du jour', 'description_mini_jeu' => 'Questions courtes sur la cuisine et le campus.', 'points_maximum' => 20],
            ['nom_mini_jeu' => 'Memo repas', 'description_mini_jeu' => 'Retrouver les paires d’articles.', 'points_maximum' => 10],
        ]);
    }
}
