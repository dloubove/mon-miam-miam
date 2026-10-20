<?php

namespace App\Http\Controllers;

use App\Exceptions\RegleMetierException;
use App\Http\Resources\CommandeResource;
use App\Mail\CommandePreteMail;
use App\Models\Commande;
use App\Services\CommandeService;
use App\Services\PaiementService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Tableau « Service en cours » du personnel : les commandes à préparer, en cuisine, prêtes,
 * et les boutons d'action. Chaque action correspond à un bouton du frontend.
 */
class StaffCommandeController extends Controller
{
    public function __construct(
        private readonly CommandeService $commandes,
        private readonly RestaurantService $restaurants,
        private readonly PaiementService $paiements,
    ) {}

    /**
     * GET /api/staff/commandes : les trois colonnes du tableau, de la plus ancienne à la plus récente.
     * Les commandes « panier » (non confirmées) et les commandes terminées n'y figurent pas.
     */
    public function index(): JsonResponse
    {
        $commandes = Commande::with(['etudiant.utilisateur', 'lignes.article', 'paiements'])
            ->where('id_restaurant', $this->restaurants->id())
            ->whereIn('statut_commande', Commande::STATUTS_EN_COURS)
            ->orderBy('date_commande')->orderBy('id_commande')
            ->get();

        // Une colonne = un statut. values() renumérote 0,1,2… pour obtenir un vrai tableau JSON.
        $colonne = fn (string $statut) => CommandeResource::collection($commandes->where('statut_commande', $statut)->values());

        return response()->json([
            'a_preparer' => $colonne('en_attente'),
            'en_cuisine' => $colonne('en_preparation'),
            'pret' => $colonne('prete'),
        ]);
    }

    /** POST /api/staff/commandes/{id}/demarrer : « Lancer la préparation ». */
    public function demarrer(Request $request, Commande $commande): CommandeResource
    {
        return $this->reponse($this->commandes->demarrer($commande, $request->user()));
    }

    /** POST /api/staff/commandes/{id}/prete : « Marquer comme PRÊT ». */
    public function prete(Request $request, Commande $commande): CommandeResource
    {
        return $this->reponse($this->commandes->marquerPrete($commande, $request->user()));
    }

    /** POST /api/staff/commandes/{id}/cloturer : « Clôturer » (servie ou livrée selon le mode). */
    public function cloturer(Request $request, Commande $commande): CommandeResource
    {
        return $this->reponse($this->commandes->cloturer($commande, $request->user()));
    }

    /** POST /api/staff/commandes/{id}/annuler : le personnel annule une commande (rupture d'ingrédient…). */
    public function annuler(Request $request, Commande $commande): CommandeResource
    {
        return $this->reponse($this->commandes->annuler($commande, $request->user()));
    }

    /** POST /api/staff/commandes/{id}/encaisser-especes : confirme la réception physique. */
    public function encaisserEspeces(Commande $commande): JsonResponse
    {
        abort_unless($commande->id_restaurant === $this->restaurants->id(), 404);
        $paiement = $this->paiements->encaisserEspeces($commande);
        return response()->json(['id_paiement' => $paiement->id_paiement, 'statut' => $paiement->statut_paiement]);
    }

    /** POST /api/staff/commandes/{id}/notifier : « Notifier » l'étudiant par email que sa commande est prête. */
    public function notifier(Commande $commande): JsonResponse
    {
        if ($commande->statut_commande !== 'prete') {
            throw new RegleMetierException("On ne peut notifier que les commandes prêtes (celle-ci est « {$commande->libelleStatut()} »).", 409);
        }

        $client = $commande->etudiant->utilisateur;
        Mail::to($client->email)->send(new CommandePreteMail($client, $commande));

        return response()->json(['message' => 'Le client a été notifié.'], 202);
    }

    /** Recharge la commande avec les informations du tableau pour la renvoyer au frontend. */
    private function reponse(Commande $commande): CommandeResource
    {
        return new CommandeResource($commande->fresh(['etudiant.utilisateur', 'lignes.article', 'historique']));
    }
}
