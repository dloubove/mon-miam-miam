<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `personnel`.
 *
 * Profil du personnel (employé, gérant, administrateur), rattaché à un compte. Un utilisateur est soit étudiant, soit personnel.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->unsignedInteger('id_utilisateur')->primary();  // Compte utilisateur concerné — clé primaire
            $table->string('matricule', 20)->unique();  // Matricule interne (ex. EMP-001)
            $table->date('date_embauche');  // Date d'embauche
            $table->unsignedInteger('id_role');  // Rôle du membre du personnel
            $table->unsignedInteger('id_restaurant')->nullable();  // Restaurant concerné
            $table->unsignedInteger('id_createur')->nullable();  // Membre du personnel ayant créé ce compte (facultatif)
            $table->timestamp('date_creation_compte')->useCurrent();  // Date de création du compte du personnel

            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->restrictOnDelete();
            $table->foreign('id_role')->references('id_role')->on('role')->restrictOnDelete();
            $table->foreign('id_restaurant')->references('id_restaurant')->on('restaurant')->restrictOnDelete();
        });

        // Clé étrangère auto-référencée : ajoutée une fois la clé primaire en place.
        Schema::table('personnel', function (Blueprint $table) {
            $table->foreign('id_createur')->references('id_utilisateur')->on('personnel')->restrictOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
