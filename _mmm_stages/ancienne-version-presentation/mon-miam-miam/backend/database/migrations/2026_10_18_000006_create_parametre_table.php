<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `parametre`.
 *
 * Réglages modifiables du restaurant (règles de fidélité, points de parrainage…), sous forme clé / valeur.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('parametre', function (Blueprint $table) {
            $table->increments('id_parametre');  // Identifiant unique, généré automatiquement
            $table->string('cle_parametre', 60);  // Nom du réglage
            $table->string('valeur_parametre', 255);  // Valeur du réglage (toujours du texte)
            $table->string('description_parametre', 255)->nullable();  // À quoi sert ce réglage
            $table->unsignedInteger('id_restaurant');  // Restaurant concerné

            $table->unique(['id_restaurant', 'cle_parametre']);
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_restaurant')->references('id_restaurant')->on('restaurant')->restrictOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('parametre');
    }
};
