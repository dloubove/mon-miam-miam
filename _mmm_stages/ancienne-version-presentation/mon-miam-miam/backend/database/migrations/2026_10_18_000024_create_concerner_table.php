<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 3 — table `concerner`.
 *
 * Table de liaison « une promotion concerne des articles » (relation plusieurs-à-plusieurs).
 * Elle ne figurait pas dans le modèle de données d'origine : sans elle, impossible de savoir
 * quels articles sont en promotion (le badge « Promo » et le prix barré du frontend).
 * Le prix remisé est CALCULÉ (prix × (1 − taux/100)), jamais stocké : un seul prix de référence.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('concerner', function (Blueprint $table) {
            $table->unsignedInteger('id_promotion'); // Promotion concernée
            $table->unsignedInteger('id_article');   // Article en promotion

            // Un article ne peut être lié qu'une fois à la même promotion.
            $table->primary(['id_promotion', 'id_article']);

            // Si la promotion ou l'article est supprimé, le lien disparaît avec lui.
            $table->foreign('id_promotion')->references('id_promotion')->on('promotion')->cascadeOnDelete();
            $table->foreign('id_article')->references('id_article')->on('article')->cascadeOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('concerner');
    }
};
