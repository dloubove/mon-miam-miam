<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `composer`.
 *
 * Table de liaison : quels articles composent quel menu du jour (relation plusieurs-à-plusieurs).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('composer', function (Blueprint $table) {
            $table->unsignedInteger('id_menu_jour');  // Menu du jour concerné
            $table->unsignedInteger('id_article');  // Article concerné

            $table->primary(['id_menu_jour', 'id_article']);
            // Clés étrangères : restrictOnDelete = impossible de supprimer une ligne encore utilisée ailleurs ;
            // cascadeOnDelete = les lignes dépendantes sont supprimées avec leur parent.
            $table->foreign('id_menu_jour')->references('id_menu_jour')->on('menu_jour')->cascadeOnDelete();
            $table->foreign('id_article')->references('id_article')->on('article')->cascadeOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('composer');
    }
};
