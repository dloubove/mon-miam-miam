<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `contenir`.
 *
 * Lignes d'une commande : quel article, en quelle quantité, à quel prix (prix figé au moment de la commande).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('contenir', function (Blueprint $table) {
            $table->unsignedInteger('id_commande');  // Commande concernée
            $table->unsignedInteger('id_article');  // Article concerné
            $table->integer('quantite');  // Nombre d'exemplaires (au moins 1)
            $table->decimal('prix_applique', 10, 0);  // Prix unitaire figé au moment de la commande

            $table->primary(['id_commande', 'id_article']);
            // Clés étrangères : restrictOnDelete = impossible de supprimer une ligne encore utilisée ailleurs ;
            // cascadeOnDelete = les lignes dépendantes sont supprimées avec leur parent.
            $table->foreign('id_commande')->references('id_commande')->on('commande')->cascadeOnDelete();
            $table->foreign('id_article')->references('id_article')->on('article')->restrictOnDelete();
        });

        // Au moins un exemplaire par ligne.
        Db::check('contenir', 'chk_contenir_quantite', 'quantite > 0');
        // Prix de la ligne strictement positif.
        Db::check('contenir', 'chk_contenir_prix', 'prix_applique > 0');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('contenir');
    }
};
