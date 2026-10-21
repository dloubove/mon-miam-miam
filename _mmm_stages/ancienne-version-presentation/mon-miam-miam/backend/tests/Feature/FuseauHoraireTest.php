<?php

namespace Tests\Feature;

use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLesCommandes;
use Tests\TestCase;

/**
 * Les dates écrites par la base (DEFAULT now()) doivent suivre le fuseau de l'application,
 * sinon elles seraient décalées de plusieurs heures par rapport à celles écrites par Laravel.
 */
class FuseauHoraireTest extends TestCase
{
    use GereLesConnexions, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    public function test_la_session_postgresql_utilise_le_fuseau_de_l_application(): void
    {
        $this->assertSame(config('app.timezone'), DB::selectOne('SHOW TIME ZONE')->TimeZone);
    }

    public function test_une_date_creee_par_la_base_correspond_a_l_heure_de_l_application(): void
    {
        $id = DB::table('commande')->insertGetId([
            'mode_service' => 'sur_place', 'heure_arrivee' => '12:00:00',
            'id_etudiant' => $this->compte('etudiant@ucac-icam.com')->id_utilisateur,
            'id_restaurant' => DB::table('restaurant')->value('id_restaurant'),
        ], 'id_commande'); // pas de date_commande : la base applique DEFAULT now()

        $commande = Commande::find($id);
        $this->assertLessThan(5, abs($commande->date_commande->diffInSeconds(now())), 'La date par défaut de la base est décalée.');
    }
}
