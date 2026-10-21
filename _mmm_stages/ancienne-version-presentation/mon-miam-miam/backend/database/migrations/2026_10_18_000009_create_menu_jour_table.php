<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `menu_jour`.
 *
 * Menu d'une journée pour un restaurant (un seul menu par jour et par restaurant).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('menu_jour', function (Blueprint $table) {
            $table->increments('id_menu_jour');  // Identifiant unique, généré automatiquement
            $table->date('date_menu');  // Jour concerné par le menu
            $table->unsignedInteger('id_restaurant');  // Restaurant concerné

            $table->unique(['id_restaurant', 'date_menu']);
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_restaurant')->references('id_restaurant')->on('restaurant')->restrictOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('menu_jour');
    }
};
