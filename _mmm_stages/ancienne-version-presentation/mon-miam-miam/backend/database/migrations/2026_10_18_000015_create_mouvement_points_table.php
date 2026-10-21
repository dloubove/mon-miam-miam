<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `mouvement_points`.
 *
 * Historique des points de fidélité : chaque gain ou utilisation est une ligne (jamais modifiée après coup).
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('mouvement_points', function (Blueprint $table) {
            $table->increments('id_mouvement');  // Identifiant unique, généré automatiquement
            $table->string('type_mouvement', 30);  // gain_commande, gain_parrainage, gain_evenement, gain_mini_jeu, utilisation, expiration ou ajustement
            $table->integer('nombre_points');  // Points ajoutés (+) ou retirés (−), jamais 0
            $table->timestamp('date_mouvement')->useCurrent();  // Date du mouvement
            $table->date('date_expiration_points')->nullable();  // Date limite d'utilisation (facultative)
            $table->unsignedInteger('id_etudiant');  // Étudiant concerné
            $table->unsignedInteger('id_commande')->nullable();  // Commande concernée

            $table->index(['id_etudiant', 'date_mouvement'], 'ix_mouvement_etudiant_date');
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_etudiant')->references('id_utilisateur')->on('etudiant')->restrictOnDelete();
            $table->foreign('id_commande')->references('id_commande')->on('commande')->restrictOnDelete();
        });

        // Valeurs autorisées pour « type_mouvement » : la base refuse toute autre valeur.
        Db::in('mouvement_points', 'type_mouvement', ['gain_commande', 'gain_parrainage', 'gain_evenement', 'gain_mini_jeu', 'utilisation', 'expiration', 'ajustement']);
        // Un mouvement de 0 point n'a pas de sens.
        Db::check('mouvement_points', 'chk_mouvement_non_nul', 'nombre_points <> 0');
        // Les gains sont positifs, utilisations et expirations négatives ; gain_commande et utilisation sont liés à une commande.
        Db::check('mouvement_points', 'chk_mouvement_coherence',
            "(type_mouvement NOT IN ('gain_commande','gain_parrainage','gain_evenement','gain_mini_jeu') OR nombre_points > 0)"
            ." AND (type_mouvement NOT IN ('utilisation','expiration') OR nombre_points < 0)"
            ." AND (type_mouvement NOT IN ('gain_commande','utilisation') OR id_commande IS NOT NULL)");
        // Un seul crédit de fidélité et une seule consommation de points par commande.
        Db::statement("CREATE UNIQUE INDEX uq_mouvement_gain_commande ON mouvement_points (id_commande) WHERE type_mouvement = 'gain_commande' AND id_commande IS NOT NULL");
        Db::statement("CREATE UNIQUE INDEX uq_mouvement_utilisation_commande ON mouvement_points (id_commande) WHERE type_mouvement = 'utilisation' AND id_commande IS NOT NULL");
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('mouvement_points');
    }
};
