<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `consentement`.
 *
 * Preuves de consentement RGPD (cookies, communications) avec la date.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('consentement', function (Blueprint $table) {
            $table->increments('id_consentement');  // Identifiant unique, généré automatiquement
            $table->string('finalite', 50);  // Objet du consentement (ex. cookies)
            $table->boolean('consentement_accorde');  // true = accepté, false = refusé
            $table->timestamp('date_consentement')->useCurrent();  // Moment où le choix a été fait
            $table->unsignedInteger('id_utilisateur');  // Compte utilisateur concerné

            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->restrictOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('consentement');
    }
};
