<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 4 — table `historique_commande`.
 *
 * Journal des changements de statut d'une commande : qui a changé quoi, et quand.
 * Elle n'existait pas dans le modèle d'origine (la commande ne garde que sa date de création et sa date de
 * validation). Elle permet d'afficher le suivi à l'étudiant avec les heures de chaque étape
 * (« 12:15 — prise en charge par le staff ») et de mesurer plus tard les temps de préparation.
 * Chaque ligne est écrite une fois et jamais modifiée.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('historique_commande', function (Blueprint $table) {
            $table->increments('id_historique');                  // Identifiant unique, généré automatiquement
            $table->unsignedInteger('id_commande');               // Commande concernée
            $table->string('statut_commande', 20);                // Statut atteint à ce moment
            $table->timestamp('date_changement')->useCurrent();   // Date et heure du changement
            $table->unsignedInteger('id_acteur')->nullable();     // Compte ayant fait le changement (étudiant ou personnel)

            $table->index(['id_commande', 'id_historique'], 'ix_historique_commande');
            // Si la commande disparaît, son journal disparaît avec elle ; un compte, lui, ne disparaît jamais (restrict).
            $table->foreign('id_commande')->references('id_commande')->on('commande')->cascadeOnDelete();
            $table->foreign('id_acteur')->references('id_utilisateur')->on('utilisateur')->restrictOnDelete();
        });

        // Valeurs autorisées pour « statut_commande » : les mêmes que la table commande.
        Db::in('historique_commande', 'statut_commande', ['panier', 'en_attente', 'en_preparation', 'prete', 'servie', 'livree', 'annulee']);
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('historique_commande');
    }
};
