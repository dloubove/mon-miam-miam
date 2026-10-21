<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jetons d'accès de l'API (« sessions » du frontend).
 *
 * À la connexion on génère un jeton aléatoire, remis UNE SEULE FOIS au client.
 * La base ne conserve que son empreinte SHA-256 : si la table fuitait,
 * les jetons ne pourraient pas être réutilisés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeton_acces', function (Blueprint $table) {
            $table->increments('id_jeton_acces');                  // identifiant technique
            $table->unsignedInteger('id_utilisateur');             // propriétaire du jeton
            $table->string('nom', 100)->default('web');            // libellé (appareil / application)
            $table->string('jeton_hash', 64)->unique();            // empreinte SHA-256 (64 caractères hexa)
            $table->timestamp('cree_le')->useCurrent();            // date de création
            $table->timestamp('derniere_utilisation')->nullable(); // dernière requête authentifiée
            $table->timestamp('expire_le');                        // au-delà, le jeton est refusé

            $table->index('id_utilisateur');
            // Si l'utilisateur disparaît, ses jetons disparaissent avec lui.
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeton_acces');
    }
};
