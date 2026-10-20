<?php

namespace Tests\Feature\Evenements;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

class MiniJeuTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_le_serveur_verifie_la_reponse_et_credit_les_points_une_seule_fois(): void
    {
        $jeu = DB::table('mini_jeu')->where('nom_mini_jeu', 'Quiz du jour')->value('id_mini_jeu');
        $question = $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/mini-jeux/{$jeu}/question", [])->assertCreated()->json();
        $reponse = DB::table('session_mini_jeu')->where('id_session', $question['session_id'])->value('reponse_attendue');
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/mini-jeux/{$jeu}/parties", [
            'session_id' => $question['session_id'], 'reponse' => $reponse,
        ])->assertOk()->assertJsonPath('points_gagnes', 20);
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/mini-jeux/{$jeu}/parties", [
            'session_id' => $question['session_id'], 'reponse' => $reponse,
        ])->assertStatus(409);

        $this->assertSame(65, (int) DB::table('etudiant')->where('code_parrainage', 'ZEDUC-DAN01')->value('solde_points'));
        $this->assertDatabaseHas('mouvement_points', ['type_mouvement' => 'gain_mini_jeu', 'nombre_points' => 20]);
    }
}
