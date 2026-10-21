<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `participer`.
 *
 * Participation d'un étudiant à un événement (score et points gagnés).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('participer', function (Blueprint $table) {
            $table->unsignedInteger('id_etudiant');  // Étudiant concerné
            $table->unsignedInteger('id_evenement');  // Événement concerné
            $table->timestamp('date_participation')->useCurrent();  // Date d'inscription à l'événement
            $table->integer('score')->nullable();  // Score obtenu (facultatif)
            $table->integer('points_gagnes')->default(0);  // Points gagnés

            $table->primary(['id_etudiant', 'id_evenement']);
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_etudiant')->references('id_utilisateur')->on('etudiant')->restrictOnDelete();
            $table->foreign('id_evenement')->references('id_evenement')->on('evenement')->restrictOnDelete();
        });

        // Score et points jamais négatifs.
        Db::check('participer', 'chk_participer_valeurs', '(score IS NULL OR score >= 0) AND points_gagnes >= 0');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('participer');
    }
};
