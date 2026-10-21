<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `evenement`.
 *
 * Événements et animations affichés aux étudiants (match, tournoi…).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('evenement', function (Blueprint $table) {
            $table->increments('id_evenement');  // Identifiant unique, généré automatiquement
            $table->string('titre_evenement', 150);  // Titre affiché
            $table->string('type_evenement', 20)->default('evenement');  // 'evenement' ou 'mini_jeu'
            $table->text('description_evenement')->nullable();  // Texte de présentation
            $table->timestamp('date_debut_evenement');  // Début
            $table->timestamp('date_fin_evenement');  // Fin (jamais avant le début)
            $table->integer('points_recompense')->default(0);  // Points offerts aux participants
            $table->string('affiche_evenement', 255)->nullable();  // Chemin de l'affiche
        });

        // Valeurs autorisées pour « type_evenement » : la base refuse toute autre valeur.
        Db::in('evenement', 'type_evenement', ['evenement', 'mini_jeu']);
        // Récompense positive ou nulle.
        Db::check('evenement', 'chk_evenement_points', 'points_recompense >= 0');
        // La fin ne précède pas le début.
        Db::check('evenement', 'chk_evenement_dates', 'date_fin_evenement >= date_debut_evenement');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('evenement');
    }
};
