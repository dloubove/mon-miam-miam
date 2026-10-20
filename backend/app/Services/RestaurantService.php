<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Restaurant « courant ».
 *
 * La base permet plusieurs restaurants, mais l'application n'en gère qu'un seul pour l'instant :
 * on prend le premier. Si un jour il y en a plusieurs, c'est ICI qu'on le déterminera
 * (par exemple selon le restaurant du membre du personnel connecté).
 */
class RestaurantService
{
    public function id(): int
    {
        return (int) DB::table('restaurant')->orderBy('id_restaurant')->value('id_restaurant');
    }
}
