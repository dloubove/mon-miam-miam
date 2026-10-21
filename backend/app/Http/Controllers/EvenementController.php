<?php

namespace App\Http\Controllers;

use App\Exceptions\RegleMetierException;
use App\Services\JeuService;
use App\Services\PointsService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvenementController extends Controller
{
    public function __construct(private readonly PointsService $points, private readonly JeuService $jeux) {}

    public function index(ImageService $images)
    {
        return DB::table('evenement')->where('date_fin_evenement', '>=', now())->orderBy('date_debut_evenement')->get()
            ->map(fn ($event) => $this->formatEvent($event, $images));
    }

    public function gererIndex(ImageService $images)
    {
        return DB::table('evenement')->orderByDesc('date_debut_evenement')->get()
            ->map(fn ($event) => $this->formatEvent($event, $images));
    }

    public function creerEvenement(Request $request, ImageService $images)
    {
        $data = $this->validerEvenement($request);
        $id = DB::table('evenement')->insertGetId([
            'titre_evenement' => $data['titre'], 'description_evenement' => $data['description'] ?? null,
            'date_debut_evenement' => $data['date_debut'], 'date_fin_evenement' => $data['date_fin'],
            'points_recompense' => $data['points_recompense'] ?? 0,
            'affiche_evenement' => $request->hasFile('affiche') ? $images->enregistrer($request->file('affiche'), 'evenements') : null,
        ], 'id_evenement');
        return response()->json($this->formatEvent(DB::table('evenement')->where('id_evenement', $id)->first(), $images), 201);
    }

    public function modifierEvenement(Request $request, int $id, ImageService $images)
    {
        $event = DB::table('evenement')->where('id_evenement', $id)->first();
        abort_unless($event, 404);
        $data = $this->validerEvenement($request, true);
        $fields = [];
        foreach (['titre' => 'titre_evenement', 'description' => 'description_evenement', 'date_debut' => 'date_debut_evenement', 'date_fin' => 'date_fin_evenement', 'points_recompense' => 'points_recompense'] as $from => $to) {
            if (array_key_exists($from, $data)) $fields[$to] = $data[$from];
        }
        if ($request->hasFile('affiche')) {
            $images->supprimer($event->affiche_evenement);
            $fields['affiche_evenement'] = $images->enregistrer($request->file('affiche'), 'evenements');
        }
        if ($fields) DB::table('evenement')->where('id_evenement', $id)->update($fields);
        return response()->json($this->formatEvent(DB::table('evenement')->where('id_evenement', $id)->first(), $images));
    }

    public function supprimerEvenement(int $id)
    {
        abort_unless(DB::table('evenement')->where('id_evenement', $id)->exists(), 404);
        if (DB::table('participer')->where('id_evenement', $id)->exists()) {
            return response()->json(['message' => 'Cet événement a déjà des participants : modifiez sa date de fin pour le masquer, son historique sera conservé.'], 409);
        }
        DB::table('evenement')->where('id_evenement', $id)->delete();
        return response()->noContent();
    }

    private function validerEvenement(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';
        return $request->validate([
            'titre' => [$required, 'string', 'min:2', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'date_debut' => [$required, 'date'],
            'date_fin' => [$required, 'date', 'after_or_equal:date_debut'],
            'points_recompense' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'affiche' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function formatEvent(object $event, ImageService $images): array
    {
        return [
            'id' => $event->id_evenement, 'titre' => $event->titre_evenement,
            'description' => $event->description_evenement, 'date_debut' => $event->date_debut_evenement,
            'date_fin' => $event->date_fin_evenement, 'points_recompense' => $event->points_recompense,
            'affiche_url' => $images->url($event->affiche_evenement),
            'actif' => now()->lte($event->date_fin_evenement),
        ];
    }

    public function jeux()
    {
        return DB::table('mini_jeu')->where('statut_mini_jeu', 'actif')->orderBy('nom_mini_jeu')->get();
    }

    public function participer(Request $request, int $evenement)
    {
        return DB::transaction(function () use ($request, $evenement) {
            $event = DB::table('evenement')->where('id_evenement', $evenement)
                ->where('date_fin_evenement', '>=', now())
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

    public function statutJeu(Request $request, int $jeu)
    {
        return response()->json($this->jeux->statut($jeu, $request->user()->id_utilisateur));
    }

    public function tournerRoue(Request $request, int $jeu)
    {
        return response()->json($this->jeux->tournerRoue($jeu, $request->user()->id_utilisateur), 201);
    }

    /** Le navigateur envoie une réponse, jamais son propre score ni le nombre de points demandé. */
    public function jouer(Request $request, int $jeu)
    {
        $data = $request->validate(['session_id' => ['required', 'uuid'], 'reponse' => ['required', 'string', 'max:100']]);
        return response()->json($this->jeux->jouer($jeu, $request->user()->id_utilisateur, $data['session_id'], $data['reponse']));
    }

    public function classement()
    {
        $restaurant = DB::table('restaurant')->orderBy('id_restaurant')->value('id_restaurant');
        return DB::table('etudiant as e')->join('utilisateur as u', 'u.id_utilisateur', '=', 'e.id_utilisateur')
            ->leftJoin('commande as c', function ($join) use ($restaurant) {
                $join->on('c.id_etudiant', '=', 'e.id_utilisateur')->where('c.id_restaurant', '=', $restaurant)
                    ->where('c.date_commande', '>=', now()->startOfMonth())
                    ->whereIn('c.statut_commande', ['en_attente', 'en_preparation', 'prete', 'servie', 'livree']);
            })
            ->whereNotNull('c.id_commande')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('paiement as p')->whereColumn('p.id_commande', 'c.id_commande')->where('p.statut_paiement', 'reussi'))
            ->select('e.id_utilisateur as id', 'u.nom as name', 'e.solde_points as points', DB::raw('COUNT(c.id_commande)::integer as orders'))
            ->groupBy('e.id_utilisateur', 'u.nom', 'e.solde_points')->orderByDesc('orders')->orderByDesc('points')->limit(10)->get();
    }
}
