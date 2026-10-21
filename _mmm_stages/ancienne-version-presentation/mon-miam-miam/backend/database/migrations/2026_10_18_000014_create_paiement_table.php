<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation étape 1 — table `paiement`.
 *
 * Paiements d'une commande (carte, mobile money, points…) et leur statut.
 */
return new class extends Migration
{
    // Création de la table (appelée par « php artisan migrate »).
    public function up(): void
    {
        Schema::create('paiement', function (Blueprint $table) {
            $table->increments('id_paiement');  // Identifiant unique, généré automatiquement
            $table->decimal('montant_paye', 10, 0);  // Montant payé en FCFA
            $table->string('mode_paiement', 20);  // carte, mobile_money, points ou especes
            $table->string('reference_transaction', 100)->nullable();  // Référence donnée par l'opérateur de paiement
            $table->string('statut_paiement', 20)->default('en_attente');  // en_attente, reussi, echoue ou rembourse
            $table->timestamp('date_paiement')->useCurrent();  // Date et heure du paiement
            $table->unsignedInteger('id_commande');  // Commande concernée

            $table->index('id_commande', 'ix_paiement_commande');
            // Clés étrangères (liens vers les autres tables). restrictOnDelete : on ne peut pas
            // supprimer une ligne encore référencée ailleurs (protège l'historique).
            $table->foreign('id_commande')->references('id_commande')->on('commande')->restrictOnDelete();
        });

        // Un paiement est strictement positif.
        Db::check('paiement', 'chk_paiement_montant', 'montant_paye > 0');
        // Valeurs autorisées pour « mode_paiement » : la base refuse toute autre valeur.
        Db::in('paiement', 'mode_paiement', ['carte', 'mobile_money', 'points', 'especes']);
        // Valeurs autorisées pour « statut_paiement » : la base refuse toute autre valeur.
        Db::in('paiement', 'statut_paiement', ['en_attente', 'reussi', 'echoue', 'rembourse']);
    }

    // Annulation (appelée par « php artisan migrate:rollback »).
    public function down(): void
    {
        Schema::dropIfExists('paiement');
    }
};
