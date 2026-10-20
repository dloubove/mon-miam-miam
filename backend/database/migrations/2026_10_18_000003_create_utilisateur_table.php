<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `utilisateur`.
 *
 * Compte de connexion de TOUTES les personnes (étudiants et personnel) : identité, email, mot de passe haché.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('utilisateur', function (Blueprint $table) {
            $table->increments('id_utilisateur');  // Identifiant unique, généré automatiquement
            $table->string('nom', 150);  // Nom complet
            $table->string('email', 150)->unique();  // Email de connexion (unique, en minuscules)
            $table->string('mot_de_passe', 255);  // Mot de passe HACHÉ (jamais en clair)
            $table->string('telephone', 20);  // Numéro de téléphone / WhatsApp
            $table->timestamp('date_inscription')->useCurrent();  // Date de création du compte
            $table->string('statut_compte', 10)->default('actif');  // 'actif' ou 'inactif' (désactivé / supprimé)
            
        });

        // Valeurs autorisées pour « statut_compte » : la base refuse toute autre valeur.
        Db::in('utilisateur', 'statut_compte', ['actif', 'inactif']);
        // Le nom ne peut pas être vide.
        Db::check('utilisateur', 'chk_utilisateur_nom', 'length(trim(nom)) > 0');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
    }
};
