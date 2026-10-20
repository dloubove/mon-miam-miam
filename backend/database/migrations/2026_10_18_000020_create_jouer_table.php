<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `jouer`.
 *
 * Parties de mini-jeux jouées par les étudiants, avec le score et les points remportés.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('jouer', function (Blueprint $table) {
            $table->bigIncrements('id_jouer');
            $table->unsignedInteger('id_etudiant');  // Étudiant concerné
            $table->unsignedInteger('id_mini_jeu');  // Mini-jeu concerné
            $table->timestamp('date_partie')->useCurrent();  // Date et heure de la partie
            $table->integer('score_partie');  // Score de la partie
            $table->integer('points_remportes')->default(0);  // Points crédités pour cette partie

            $table->index(['id_etudiant', 'id_mini_jeu', 'date_partie'], 'ix_jouer_etudiant_jeu_date');
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_etudiant')->references('id_utilisateur')->on('etudiant')->restrictOnDelete();
            $table->foreign('id_mini_jeu')->references('id_mini_jeu')->on('mini_jeu')->restrictOnDelete();
        });

        // Score et points jamais négatifs.
        Db::check('jouer', 'chk_jouer_valeurs', 'score_partie >= 0 AND points_remportes >= 0');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('jouer');
    }
};
