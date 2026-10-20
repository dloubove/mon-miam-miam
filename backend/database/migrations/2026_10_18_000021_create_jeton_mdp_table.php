<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `jeton_mdp`.
 *
 * Jetons à usage unique pour réinitialiser un mot de passe oublié (seule l'empreinte est stockée).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('jeton_mdp', function (Blueprint $table) {
            $table->increments('id_jeton');  // Identifiant unique, généré automatiquement
            $table->string('jeton_hash', 255)->unique();  // Empreinte SHA-256 du jeton (le jeton en clair part par email)
            $table->timestamp('date_expiration_jeton');  // Le lien devient invalide après cette date
            $table->boolean('jeton_utilise')->default(false);  // true dès que le lien a servi (usage unique)
            $table->unsignedInteger('id_utilisateur');  // Compte utilisateur concerné

            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->restrictOnDelete();
        });
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('jeton_mdp');
    }
};
