<?php

use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Load required lookup data once on existing and newly created deployments.
     */
    public function up(): void
    {
        DB::transaction(fn () => app(ReferenceSeeder::class)->run());
    }

    /**
     * Keep reference rows in place on rollback; they may already be in use.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
