<?php

namespace App\Services;

use App\Exceptions\RegleMetierException;
use App\Models\Etudiant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Les questions et le calcul du résultat restent côté serveur pour empêcher le client de choisir ses points. */
class JeuService
{
    private const QUESTIONS = [
        ['question' => 'Quel plat est préparé avec du poisson braisé ?', 'options' => ['Eru', 'Poisson Braisé', 'Riz Bolognaise', 'Okok'], 'reponse' => 'Poisson Braisé'],
        ['question' => 'Quel accompagnement est servi avec le poulet pané ?', 'options' => ['Frites', 'Bâtons', 'Riz sauté', 'Plantains'], 'reponse' => 'Frites'],
        ['question' => 'Quel plat camerounais est préparé avec des feuilles de manioc ?', 'options' => ['Eru', 'Rôti de porc', 'Poulet mayo', 'Poisson Braisé'], 'reponse' => 'Eru'],
        ['question' => 'Quel jus est naturellement préparé avec le pamplemousse ?', 'options' => ['Jus Planet', 'Jus Spécial Pamplemousse', 'Booster Cola', 'Booster mangue-Orange'], 'reponse' => 'Jus Spécial Pamplemousse'],
    ];
    private const ROUE_PRIX = ['Perdu', '500 FCFA de réduction', 'Boisson offerte', '1 point', '100 FCFA de réduction', '1 point'];

    public function __construct(private readonly PointsService $points) {}

    public function nouvelleQuestion(int $jeu, int $etudiant): array
    {
        abort_unless(DB::table('mini_jeu')->where('id_mini_jeu', $jeu)->where('statut_mini_jeu', 'actif')->exists(), 404, 'Jeu introuvable.');
        $this->verifierJeu($jeu);
        $this->verifierTentativeQuotidienne($jeu, $etudiant);
        $question = self::QUESTIONS[random_int(0, count(self::QUESTIONS) - 1)];
        $session = (string) Str::uuid();
        DB::table('session_mini_jeu')->insert([
            'id_session' => $session, 'id_etudiant' => $etudiant, 'id_mini_jeu' => $jeu,
            'question' => $question['question'], 'reponse_attendue' => $question['reponse'],
            'date_expiration' => now()->addMinutes(5),
        ]);
        return ['session_id' => $session, 'question' => $question['question'], 'options' => $question['options'], 'expire_dans_secondes' => 300];
    }

    public function jouer(int $jeu, int $etudiant, string $sessionId, string $reponse): array
    {
        return DB::transaction(function () use ($jeu, $etudiant, $sessionId, $reponse) {
            Etudiant::whereKey($etudiant)->lockForUpdate()->firstOrFail();
            $this->verifierTentativeQuotidienne($jeu, $etudiant);
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

    public function tournerRoue(int $jeu, int $etudiant): array
    {
        return DB::transaction(function () use ($jeu, $etudiant) {
            Etudiant::whereKey($etudiant)->lockForUpdate()->firstOrFail();
            $this->verifierJeu($jeu);
            $this->verifierTentativeQuotidienne($jeu, $etudiant);
            $prix = self::ROUE_PRIX[random_int(0, count(self::ROUE_PRIX) - 1)];
            $points = $prix === '1 point' ? 1 : 0;
            DB::table('jouer')->insert([
                'id_etudiant' => $etudiant, 'id_mini_jeu' => $jeu, 'date_partie' => now(),
                'score_partie' => $points ? 100 : 0, 'points_remportes' => $points,
                'recompense_libelle' => $prix,
            ]);
            if ($points > 0) $this->points->enregistrer($etudiant, 'gain_mini_jeu', $points);
            return ['recompense' => $prix, 'points_gagnes' => $points];
        });
    }

    public function statut(int $jeu, int $etudiant): array
    {
        $this->verifierJeu($jeu);
        return ['joue_aujourdhui' => DB::table('jouer')->where('id_etudiant', $etudiant)->where('id_mini_jeu', $jeu)
            ->where('date_partie', '>=', now()->startOfDay())->exists()];
    }

    private function verifierJeu(int $jeu): void
    {
        abort_unless(DB::table('mini_jeu')->where('id_mini_jeu', $jeu)->where('statut_mini_jeu', 'actif')->exists(), 404, 'Jeu introuvable ou désactivé.');
    }

    private function verifierTentativeQuotidienne(int $jeu, int $etudiant): void
    {
        $dejaJoue = DB::table('jouer')->where('id_etudiant', $etudiant)->where('id_mini_jeu', $jeu)
            ->where('date_partie', '>=', now()->startOfDay())->exists();
        if ($dejaJoue) throw new RegleMetierException('Vous avez déjà joué à ce jeu aujourd’hui. Revenez demain.', 409);
    }
}
