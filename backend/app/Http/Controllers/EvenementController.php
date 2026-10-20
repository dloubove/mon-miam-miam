<?php

namespace App\Http\Controllers;

use App\Exceptions\RegleMetierException;
use App\Models\Etudiant;
use App\Services\JeuService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvenementController extends Controller
{
    public function __construct(private readonly PointsService $points, private readonly JeuService $jeux) {}

    public function index()
    {
        return DB::table('evenement')->where('date_fin_evenement', '>=', now())->orderBy('date_debut_evenement')->get();
    }

    public function jeux()
    {
        return DB::table('mini_jeu')->where('statut_mini_jeu', 'actif')->orderBy('nom_mini_jeu')->get();
    }

    public function participer(Request $request, int $evenement)
    {
        return DB::transaction(function () use ($request, $evenement) {
            $event = DB::table('evenement')->where('id_evenement', $evenement)
                ->where('date_debut_evenement', '<=', now())->where('date_fin_evenement', '>=', now())
                ->lockForUpdate()->first();
            abort_unless($event, 404, 'Événement non disponible.');
            $etudiant = $request->user()->id_utilisateur;
            $existant = DB::table('participer')->where(['id_etudiant' => $etudiant, 'id_evenement' => $evenement])->exists();
            if ($existant) throw new RegleMetierException('Vous participez déjà à cet événement.', 409);
            DB::table('participer')->insert(['id_etudiant' => $etudiant, 'id_evenement' => $evenement, 'date_participation' => now(), 'points_gagnes' => $event->points_recompense]);
            if ($event->points_recompense > 0) $this->points->enregistrer($etudiant, 'gain_evenement', $event->points_recompense);
            return response()->json(['message' => 'Participation enregistrée.', 'points_gagnes' => $event->points_recompense], 201);
        });
    }

    public function question(Request $request, int $jeu)
    {
        return response()->json($this->jeux->nouvelleQuestion($jeu, $request->user()->id_utilisateur), 201);
    }

    /** Le navigateur envoie une réponse, jamais son propre score ni le nombre de points demandé. */
    public function jouer(Request $request, int $jeu)
    {
        $data = $request->validate(['session_id' => ['required', 'uuid'], 'reponse' => ['required', 'string', 'max:100']]);
        return response()->json($this->jeux->jouer($jeu, $request->user()->id_utilisateur, $data['session_id'], $data['reponse']));
    }

    public function classement()
    {
        return Etudiant::with('utilisateur:id_utilisateur,nom')
            ->orderByDesc('solde_points')->limit(20)->get(['id_utilisateur', 'solde_points']);
    }
}
