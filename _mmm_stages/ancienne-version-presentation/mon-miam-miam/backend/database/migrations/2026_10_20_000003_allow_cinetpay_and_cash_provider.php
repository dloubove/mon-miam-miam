<?php

use App\Support\DbConstraints as Db;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Db::statement('ALTER TABLE paiement DROP CONSTRAINT IF EXISTS chk_paiement_fournisseur');
        Db::in('paiement', 'fournisseur', ['simulation', 'orange_money', 'mtn_momo', 'cinetpay', 'especes']);
    }

    public function down(): void
    {
        Db::statement('ALTER TABLE paiement DROP CONSTRAINT IF EXISTS chk_paiement_fournisseur');
        Db::in('paiement', 'fournisseur', ['simulation', 'orange_money', 'mtn_momo']);
    }
};
