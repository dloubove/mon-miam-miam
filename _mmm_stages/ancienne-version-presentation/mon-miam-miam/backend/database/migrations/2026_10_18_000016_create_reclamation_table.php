<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `reclamation`.
 *
 * Réclamations des étudiants : réponse du personnel puis décision du gérant.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('reclamation', function (Blueprint $table) {
            $table->increments('id_reclamation');  // Identifiant unique, généré automatiquement
            $table->string('objet', 150);  // Sujet de la réclamation
            $table->text('description_reclamation');  // Message de l'étudiant
            $table->timestamp('date_reclamation')->useCurrent();  // Date d'envoi
            $table->string('statut_reclamation', 20)->default('ouverte');  // ouverte, repondue, validee ou rejetee
            $table->unsignedInteger('id_etudiant');  // Étudiant concerné
            $table->unsignedInteger('id_commande')->nullable();  // Commande concernée
            $table->text('reponse_proposee')->nullable();  // Réponse proposée par le personnel
            $table->timestamp('date_reponse')->nullable();  // Date de la réponse
            $table->unsignedInteger('id_repondant')->nullable();  // Personnel ayant répondu (facultatif)
            $table->string('decision_gerant', 10)->nullable();  // 'validee' ou 'rejetee'
            $table->text('motif_decision')->nullable();  // Explication de la décision
            $table->timestamp('date_decision')->nullable();  // Date de la décision
            $table->unsignedInteger('id_decideur')->nullable();  // Gérant ayant décidé (facultatif)

            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_etudiant')->references('id_utilisateur')->on('etudiant')->restrictOnDelete();
            $table->foreign('id_commande')->references('id_commande')->on('commande')->restrictOnDelete();
            $table->foreign('id_repondant')->references('id_utilisateur')->on('personnel')->restrictOnDelete();
            $table->foreign('id_decideur')->references('id_utilisateur')->on('personnel')->restrictOnDelete();
        });

        // Valeurs autorisées pour « statut_reclamation » : la base refuse toute autre valeur.
        Db::in('reclamation', 'statut_reclamation', ['ouverte', 'repondue', 'validee', 'rejetee']);
        // Valeurs autorisées pour « decision_gerant » : la base refuse toute autre valeur.
        Db::in('reclamation', 'decision_gerant', ['validee', 'rejetee']);
        // Le gérant ne peut décider qu'après la réponse du personnel, et dans l'ordre chronologique.
        Db::check('reclamation', 'chk_reclamation_decision',
            'decision_gerant IS NULL OR (reponse_proposee IS NOT NULL AND date_reponse IS NOT NULL AND date_decision IS NOT NULL AND date_decision >= date_reponse)');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('reclamation');
    }
};
