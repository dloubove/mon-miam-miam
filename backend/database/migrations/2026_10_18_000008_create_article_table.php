<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `article`.
 *
 * Catalogue : les plats et boissons vendus, avec leur prix et leur disponibilité.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('article', function (Blueprint $table) {
            $table->increments('id_article');  // Identifiant unique, généré automatiquement
            $table->string('nom_article', 150);  // Nom affiché de l'article
            $table->text('description_article')->nullable();  // Description / ingrédients
            $table->decimal('prix_unitaire', 10, 0);  // Prix en FCFA (entier, strictement positif)
            $table->string('image_article', 255)->nullable();  // Chemin de la photo
            $table->boolean('disponible')->default(true);  // false = en rupture de stock
            $table->unsignedInteger('id_categorie');  // Catégorie de l'article
            $table->unsignedInteger('id_restaurant');  // Restaurant concerné

            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_categorie')->references('id_categorie')->on('categorie')->restrictOnDelete();
            $table->foreign('id_restaurant')->references('id_restaurant')->on('restaurant')->restrictOnDelete();
        });

        // Un prix est strictement positif.
        Db::check('article', 'chk_article_prix', 'prix_unitaire > 0');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('article');
    }
};
