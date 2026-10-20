<?php

namespace App\Http\Controllers;

use App\Exceptions\RegleMetierException;
use App\Models\Commande;
use App\Models\Reclamation;
use Illuminate\Http\Request;

class ReclamationController extends Controller
{
    public function mesReclamations(Request $request)
    {
        return Reclamation::where('id_etudiant', $request->user()->id_utilisateur)->latest('date_reclamation')->paginate(10);
    }

    public function creer(Request $request): Reclamation
    {
        $data = $request->validate([
            'objet' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'commande_id' => ['nullable', 'integer'],
        ]);
        if (! empty($data['commande_id'])) {
            Commande::where('id_etudiant', $request->user()->id_utilisateur)->findOrFail($data['commande_id']);
        }
        return Reclamation::create([
            'objet' => $data['objet'], 'description_reclamation' => $data['description'],
            'date_reclamation' => now(), 'statut_reclamation' => 'ouverte',
            'id_etudiant' => $request->user()->id_utilisateur, 'id_commande' => $data['commande_id'] ?? null,
        ]);
    }

    public function index(Request $request)
    {
        return Reclamation::with('etudiant.utilisateur')->orderBy('date_reclamation')->paginate(20);
    }

    public function repondre(Request $request, Reclamation $reclamation): Reclamation
    {
        if ($reclamation->statut_reclamation !== 'ouverte') {
            throw new RegleMetierException('Cette réclamation a déjà reçu une réponse.', 409);
        }
        $data = $request->validate(['reponse' => ['required', 'string', 'min:3', 'max:5000']]);
        $reclamation->update([
            'reponse_proposee' => $data['reponse'], 'date_reponse' => now(),
            'id_repondant' => $request->user()->id_utilisateur, 'statut_reclamation' => 'repondue',
        ]);
        return $reclamation->fresh();
    }

    public function decider(Request $request, Reclamation $reclamation): Reclamation
    {
        if ($reclamation->statut_reclamation !== 'repondue') {
            throw new RegleMetierException('La réclamation doit d’abord recevoir une réponse du personnel.', 409);
        }
        $data = $request->validate(['decision' => ['required', 'in:validee,rejetee'], 'motif' => ['required', 'string', 'min:3', 'max:2000']]);
        $reclamation->update([
            'decision_gerant' => $data['decision'], 'motif_decision' => $data['motif'],
            'date_decision' => now(), 'id_decideur' => $request->user()->id_utilisateur,
            'statut_reclamation' => $data['decision'],
        ]);
        return $reclamation->fresh();
    }
}
