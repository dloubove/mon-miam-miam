<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `role`.
 *
 * Rôles du personnel : employé, gérant, administrateur. Table de référence, remplie par ReferenceSeeder.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('role', function (Blueprint $table) {
            $table->increments('id_role');  // Identifiant unique, généré automatiquement
            $table->string('libelle_role', 30)->unique();  // Nom du rôle
        });

        // Valeurs autorisées pour « libelle_role » : la base refuse toute autre valeur.
        Db::in('role', 'libelle_role', ['employe', 'gerant', 'administrateur']);
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('role');
    }
};
