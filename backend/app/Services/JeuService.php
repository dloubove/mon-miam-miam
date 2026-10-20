<?php

namespace App\Services;

use App\Exceptions\RegleMetierException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Les questions et le calcul du résultat restent côté serveur pour empêcher le client de choisir ses points. */
class JeuService
{
    private const QUESTIONS = [
        ['question' => 'Combien font 2 + 2 ?', 'reponse' => '4'],
        ['question' => 'Quel fruit donne le jus d’orange ?', 'reponse' => 'orange'],
        ['question' => 'Combien font 3 × 3 ?', 'reponse' => '9'],
        ['question' => 'Quel aliment est une céréale : riz ou poulet ?', 'reponse' => 'riz'],
    ];

    public function __construct(private readonly PointsService $points) {}

    public function nouvelleQuestion(int $jeu, int $etudiant): array
    {
        abort_unless(DB::table('mini_jeu')->where('id_mini_jeu', $jeu)->where('statut_mini_jeu', 'actif')->exists(), 404, 'Jeu introuvable.');
        $question = self::QUESTIONS[random_int(0, count(self::QUESTIONS) - 1)];
        $session = (string) Str::uuid();
        DB::table('session_mini_jeu')->insert([
            'id_session' => $session, 'id_etudiant' => $etudiant, 'id_mini_jeu' => $jeu,
            'question' => $question['question'], 'reponse_attendue' => $question['reponse'],
            'date_expiration' => now()->addMinutes(5),
        ]);
        return ['session_id' => $session, 'question' => $question['question'], 'expire_dans_secondes' => 300];
    }

    public function jouer(int $jeu, int $etudiant, string $sessionId, string $reponse): array
    {
        return DB::transaction(function () use ($jeu, $etudiant, $sessionId, $reponse) {
            $session = DB::table('session_mini_jeu')->where('id_session', $sessionId)->lockForUpdate()->first();
            if (! $session || (int) $session->id_etudiant !== $etudiant || (int) $session->id_mini_jeu !== $jeu) {
                throw new RegleMetierException('Session de jeu introuvable.', 404);
            }
            if ($session->date_utilisation || Carbon::parse($session->date_expiration)->isPast()) {
                throw new RegleMetierException('Cette session a expiré ou a déjà été utilisée.', 409);
            }
            $game = DB::table('mini_jeu')->where('id_mini_jeu', $jeu)->where('statut_mini_jeu', 'actif')->first();
            abort_unless($game, 404, 'Jeu introuvable.');
            $correct = mb_strtolower(trim($reponse)) === mb_strtolower(trim($session->reponse_attendue));
            $score = $correct ? 100 : 0;
            $gagnes = $correct ? $game->points_maximum : 0;
            DB::table('session_mini_jeu')->where('id_session', $sessionId)->update(['date_utilisation' => now()]);
            DB::table('jouer')->insert([
                'id_etudiant' => $etudiant, 'id_mini_jeu' => $jeu, 'date_partie' => now(),
                'score_partie' => $score, 'points_remportes' => $gagnes,
            ]);
            if ($gagnes > 0) $this->points->enregistrer($etudiant, 'gain_mini_jeu', $gagnes);
            return ['correct' => $correct, 'score' => $score, 'points_gagnes' => $gagnes];
        });
    }
}
