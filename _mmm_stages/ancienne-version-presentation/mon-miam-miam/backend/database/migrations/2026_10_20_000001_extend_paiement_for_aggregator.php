<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement', function (Blueprint $table) {
            $table->string('fournisseur', 40)->default('simulation')->after('mode_paiement');
            $table->string('cle_idempotence', 100)->nullable()->unique()->after('reference_transaction');
            $table->string('url_paiement', 500)->nullable()->after('cle_idempotence');
            $table->json('reponse_fournisseur')->nullable()->after('url_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('paiement', function (Blueprint $table) {
            $table->dropUnique(['cle_idempotence']);
            $table->dropColumn(['fournisseur', 'cle_idempotence', 'url_paiement', 'reponse_fournisseur']);
        });
    }
};
