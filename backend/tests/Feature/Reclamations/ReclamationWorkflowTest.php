<?php

namespace Tests\Feature\Reclamations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

class ReclamationWorkflowTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_etudiant_personnel_et_gerant_suivent_le_flux_de_reclamation(): void
    {
        $id = $this->enTantQue('etudiant@ucac-icam.com')->postJson('/api/reclamations', [
            'objet' => 'Commande incomplète', 'description' => 'Il manque une boisson dans ma commande.',
        ])->assertCreated()->json('id_reclamation');

        $this->app['auth']->forgetGuards();
        $this->enTantQue('paul@zeduc.com')->postJson("/api/staff/reclamations/{$id}/reponse", ['reponse' => 'Nous avons vérifié et préparons un avoir.'])
            ->assertOk()->assertJsonPath('statut_reclamation', 'repondue');

        $this->app['auth']->forgetGuards();
        $this->enTantQue('emma@zeduc.com')->postJson("/api/gestion/reclamations/{$id}/decision", [
            'decision' => 'validee', 'motif' => 'Le justificatif confirme le problème.',
        ])->assertOk()->assertJsonPath('statut_reclamation', 'validee');
    }
}
