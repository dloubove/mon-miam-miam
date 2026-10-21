<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `restaurant`.
 *
 * Le restaurant ZeDuc@Space. Un seul pour l'instant, mais la structure permet d'en ajouter d'autres.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('restaurant', function (Blueprint $table) {
            $table->increments('id_restaurant');  // Identifiant unique, généré automatiquement
            $table->string('nom_restaurant', 150);  // Nom du restaurant
            $table->string('adresse_restaurant', 255);  // Adresse postale
            $table->string('telephone_restaurant', 20);  // Téléphone du restaurant
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('restaurant');
    }
};
