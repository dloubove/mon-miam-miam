<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `commande`.
 *
 * Commande d'un étudiant : statut, mode de service (sur place / livraison), montants et points de fidélité.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('commande', function (Blueprint $table) {
            $table->increments('id_commande');  // Identifiant unique, généré automatiquement
            $table->timestamp('date_commande')->useCurrent();  // Date et heure de création de la commande
            $table->string('statut_commande', 20)->default('panier');  // panier, en_attente, en_preparation, prete, servie, livree ou annulee
            $table->string('mode_service', 15);  // 'sur_place' ou 'livraison'
            $table->time('heure_arrivee')->nullable();  // Heure d'arrivée (obligatoire si sur place)
            $table->string('adresse_livraison', 255)->nullable();  // Adresse (obligatoire si livraison)
            $table->decimal('montant_total', 10, 0)->default(0);  // Total à payer en FCFA, après réduction
            $table->integer('points_utilises')->default(0);  // Points dépensés sur cette commande
            $table->decimal('reduction_points', 10, 0)->default(0);  // Réduction obtenue grâce aux points, en FCFA
            $table->integer('points_gagnes')->default(0);  // Points gagnés
            $table->text('commentaire_client')->nullable();  // Remarque de l'étudiant pour le chef
            $table->timestamp('date_commentaire')->nullable();  // Date du commentaire
            $table->timestamp('date_validation')->nullable();  // Date de validation par le personnel
            $table->unsignedInteger('id_etudiant');  // Étudiant concerné
            $table->unsignedInteger('id_restaurant');  // Restaurant concerné
            $table->unsignedInteger('id_validateur')->nullable();  // Personnel ayant validé la commande (facultatif)
            $table->unsignedInteger('id_promotion')->nullable();  // Promotion appliquée (facultatif)

            $table->index(['id_etudiant', 'date_commande'], 'ix_commande_etudiant_date');
            $table->index(['id_restaurant', 'date_commande'], 'ix_commande_restaurant_date');
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_etudiant')->references('id_utilisateur')->on('etudiant')->restrictOnDelete();
            $table->foreign('id_restaurant')->references('id_restaurant')->on('restaurant')->restrictOnDelete();
            $table->foreign('id_validateur')->references('id_utilisateur')->on('personnel')->restrictOnDelete();
            $table->foreign('id_promotion')->references('id_promotion')->on('promotion')->restrictOnDelete();
        });

        // Valeurs autorisées pour « statut_commande » : la base refuse toute autre valeur.
        Db::in('commande', 'statut_commande', ['panier', 'en_attente', 'en_preparation', 'prete', 'servie', 'livree', 'annulee']);
        // Valeurs autorisées pour « mode_service » : la base refuse toute autre valeur.
        Db::in('commande', 'mode_service', ['sur_place', 'livraison']);
        // Montants jamais négatifs.
        Db::check('commande', 'chk_commande_montants', 'montant_total >= 0 AND reduction_points >= 0');
        // Points jamais négatifs.
        Db::check('commande', 'chk_commande_points', 'points_utilises >= 0 AND points_gagnes >= 0');
        // Sur place → heure d'arrivée obligatoire ; livraison → adresse obligatoire.
        Db::check('commande', 'chk_commande_mode_service_donnees',
            "(mode_service = 'sur_place' AND heure_arrivee IS NOT NULL) OR (mode_service = 'livraison' AND adresse_livraison IS NOT NULL)");
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('commande');
    }
};
