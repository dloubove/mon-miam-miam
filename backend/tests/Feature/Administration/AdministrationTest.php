<?php

namespace Tests\Feature\Administration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_tableau_de_bord_est_reserve_a_l_administrateur(): void
    {
        $this->enTantQue('admin@zeduc.com')->getJson('/api/administration/tableau-de-bord')->assertOk()
            ->assertJsonStructure(['utilisateurs', 'etudiants', 'commandes_en_cours', 'commandes_payees', 'chiffre_affaires_fcfa', 'reclamations_ouvertes']);
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->getJson('/api/administration/tableau-de-bord')->assertForbidden();
    }
}
