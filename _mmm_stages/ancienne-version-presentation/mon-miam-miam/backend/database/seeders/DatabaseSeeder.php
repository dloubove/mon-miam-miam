<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);

        // Les comptes et plats de démonstration ne sont jamais créés en production.
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }

        // Les commandes de démonstration (tableau du personnel, historique) n'existent qu'en développement local :
        // pas en production, et pas pendant les tests (qui créent leurs propres commandes).
        if (app()->environment('local')) {
            $this->call(DemoCommandesSeeder::class);
        }
    }
}
