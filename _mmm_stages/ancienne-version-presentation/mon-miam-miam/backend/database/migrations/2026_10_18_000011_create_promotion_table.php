<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `promotion`.
 *
 * Promotions temporaires (réduction en %, période de validité, affiche).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('promotion', function (Blueprint $table) {
            $table->increments('id_promotion');  // Identifiant unique, généré automatiquement
            $table->string('titre_promotion', 150);  // Titre de la promotion
            $table->text('description_promotion')->nullable();  // Détail de l'offre
            $table->decimal('taux_reduction', 5, 2);  // Pourcentage de réduction (0 à 100)
            $table->date('date_debut_promo');  // Premier jour de la promotion
            $table->date('date_fin_promo');  // Dernier jour de la promotion
            $table->string('affiche_promo', 255)->nullable();  // Chemin de l'affiche
        });

        // Une réduction est comprise entre 0 et 100 %.
        Db::check('promotion', 'chk_promotion_taux', 'taux_reduction BETWEEN 0 AND 100');
        // Une promotion ne peut pas finir avant de commencer.
        Db::check('promotion', 'chk_promotion_dates', 'date_fin_promo >= date_debut_promo');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('promotion');
    }
};
