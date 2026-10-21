<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `categorie`.
 *
 * Catégories d'articles : plat, boisson, dessert.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('categorie', function (Blueprint $table) {
            $table->increments('id_categorie');  // Identifiant unique, généré automatiquement
            $table->string('libelle_categorie', 150)->unique();  // Nom de la catégorie
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('categorie');
    }
};
