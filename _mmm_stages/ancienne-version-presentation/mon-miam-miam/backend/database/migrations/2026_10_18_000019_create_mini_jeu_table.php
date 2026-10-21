<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `mini_jeu`.
 *
 * Mini-jeux disponibles (quiz, memo…) et points maximum gagnables.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('mini_jeu', function (Blueprint $table) {
            $table->increments('id_mini_jeu');  // Identifiant unique, généré automatiquement
            $table->string('nom_mini_jeu', 150)->unique();  // Nom du jeu (unique)
            $table->text('description_mini_jeu')->nullable();  // Règles / présentation
            $table->integer('points_maximum');  // Maximum de points gagnables en une partie
            $table->string('statut_mini_jeu', 10)->default('actif');  // 'actif' ou 'inactif'
            $table->string('affiche_mini_jeu', 255)->nullable();  // Chemin de l'image
        });

        // Le maximum de points est strictement positif.
        Db::check('mini_jeu', 'chk_mini_jeu_points', 'points_maximum > 0');
        // Valeurs autorisées pour « statut_mini_jeu » : la base refuse toute autre valeur.
        Db::in('mini_jeu', 'statut_mini_jeu', ['actif', 'inactif']);
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('mini_jeu');
    }
};
