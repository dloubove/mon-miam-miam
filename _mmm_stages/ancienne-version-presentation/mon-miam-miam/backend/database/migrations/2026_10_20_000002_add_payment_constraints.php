<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Db::in('paiement', 'fournisseur', ['simulation', 'orange_money', 'mtn_momo']);
        Db::statement("CREATE UNIQUE INDEX uq_paiement_commande_en_attente ON paiement (id_commande) WHERE statut_paiement = 'en_attente'");
    }

    public function down(): void
    {
        Db::statement('DROP INDEX IF EXISTS uq_paiement_commande_en_attente');
        Db::statement('ALTER TABLE paiement DROP CONSTRAINT IF EXISTS chk_paiement_fournisseur');
    }
};
