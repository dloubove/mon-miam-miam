<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `etudiant`.
 *
 * Profil étudiant, rattaché à un compte : solde de points de fidélité, code et lien de parrainage.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('etudiant', function (Blueprint $table) {
            $table->unsignedInteger('id_utilisateur')->primary();  // Compte utilisateur concerné — clé primaire
            $table->string('code_parrainage', 20)->unique();  // Code à donner à ses amis (ex. ZEDUC-A98X2)
            $table->integer('solde_points')->default(0);  // Points de fidélité disponibles (jamais négatif)
            $table->string('localisation', 255)->nullable();  // Lieu de livraison habituel
            $table->unsignedInteger('id_parrain')->nullable();  // Étudiant parrain (facultatif)
            $table->timestamp('date_parrainage')->nullable();  // Date d'inscription avec le code d'un parrain
            $table->boolean('recompense_attribuee')->default(false);  // Le parrain a-t-il déjà reçu sa récompense ?
            $table->integer('points_parrainage')->nullable();  // Points que recevra le parrain

            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->restrictOnDelete();
        });

        // Clé étrangère auto-référencée : ajoutée une fois la clé primaire en place.
        Schema::table('etudiant', function (Blueprint $table) {
            $table->foreign('id_parrain')->references('id_utilisateur')->on('etudiant')->restrictOnDelete();
        });

        // Le solde de points ne peut jamais être négatif.
        Db::check('etudiant', 'chk_etudiant_solde', 'solde_points >= 0');
        // La récompense de parrainage est positive ou absente.
        Db::check('etudiant', 'chk_etudiant_points_parrainage', 'points_parrainage IS NULL OR points_parrainage >= 0');
        // Un étudiant ne peut pas être son propre parrain.
        Db::check('etudiant', 'chk_etudiant_parrain_distinct', 'id_parrain IS NULL OR id_parrain <> id_utilisateur');
        // Parrain et date de parrainage vont toujours ensemble.
        Db::check('etudiant', 'chk_etudiant_parrainage_date', '(id_parrain IS NULL) = (date_parrainage IS NULL)');
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('etudiant');
    }
};
