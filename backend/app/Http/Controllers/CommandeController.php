<?php

namespace App\Http\Controllers;

use App\Exceptions\RegleMetierException;
use App\Http\Requests\CommandeRequest;
use App\Http\Requests\FiltreCommandesRequest;
use App\Http\Resources\CommandeResource;
use App\Models\Commande;
use App\Services\CommandeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Commandes vues par l'étudiant : devis, création, suivi, annulation.
 * Toutes les routes sont réservées aux étudiants (middleware role:etudiant) et ne donnent accès
 * qu'à LEURS commandes.
 */
class CommandeController extends Controller
{
    public function __construct(private readonly CommandeService $commandes) {}

    /**
     * POST /api/commandes/devis : calcule le prix exact (promotions, réduction par points, points à gagner)
     * sans rien enregistrer. Le frontend l'appelle pour afficher la page panier.
     */
    public function devis(CommandeRequest $request): JsonResponse
    {
        $devis = $this->commandes->chiffrer($request->user(), $request->validated()['lignes'], $request->boolean('utiliser_points'));

        return response()->json([
            'lignes' => array_map(fn ($l) => [
                'article_id' => $l['article']->id_article,
                'nom' => $l['article']->nom_article,
                'quantite' => $l['quantite'],
                'prix_unitaire' => $l['prix_unitaire'],
                'total' => $l['total'],
            ], $devis['lignes']),
            'sous_total' => $devis['sous_total'],
            'points_utilises' => $devis['points_utilises'],
            'reduction_points' => $devis['reduction_points'],
            'total' => $devis['total'],
            'points_a_gagner' => $devis['points_a_gagner'],
            'solde_points' => $request->user()->etudiant->solde_points,
        ]);
    }

    /** POST /api/commandes : crée la commande (statut « panier » = en attente de paiement). */
    public function store(CommandeRequest $request): JsonResponse
    {
        $commande = $this->commandes->creer($request->user(), $request->validated());

        return (new CommandeResource($this->charger($commande)))->response()->setStatusCode(201);
    }

    /**
     * GET /api/commandes : mes commandes, les plus récentes d'abord.
     * ?etat=en_cours pour la bannière « Ta commande est en préparation », ?etat=terminees pour l'historique.
     */
    public function index(FiltreCommandesRequest $request): AnonymousResourceCollection
    {
        $requete = Commande::where('id_etudiant', $request->user()->id_utilisateur)
            ->with(['lignes.article', 'historique'])
            ->orderByDesc('date_commande')->orderByDesc('id_commande');

        match ($request->validated()['etat'] ?? null) {
            'a_payer' => $requete->where('statut_commande', 'panier'),
            'en_cours' => $requete->whereIn('statut_commande', Commande::STATUTS_EN_COURS),
            'terminees' => $requete->whereIn('statut_commande', Commande::STATUTS_TERMINES),
            default => null,
        };

        return CommandeResource::collection($requete->paginate((int) ($request->validated()['par_page'] ?? 10)));
    }

    /** GET /api/commandes/{id} : le détail et la frise de suivi d'une commande. */
    public function show(Request $request, int $id): CommandeResource
    {
        return new CommandeResource($this->charger($this->mienne($request, $id)));
    }

    /**
     * POST /api/commandes/{id}/confirmer : confirme la commande SANS paiement.
     * Réservé au mode de test (COMMANDES_CONFIRMATION_DIRECTE=true) tant que le paiement n'existe pas (étape 5).
     */
    public function confirmer(Request $request, int $id): CommandeResource
    {
        if (! config('commandes.confirmation_directe')) {
            throw new RegleMetierException('Le paiement est requis pour confirmer une commande.', 403);
        }

        $commande = $this->commandes->confirmer($this->mienne($request, $id), $request->user());

        return new CommandeResource($this->charger($commande));
    }

    /**
     * POST /api/commandes/{id}/annuler : l'étudiant annule sa commande tant qu'elle n'est pas en préparation.
     * Ses points éventuellement dépensés lui sont rendus.
     */
    public function annuler(Request $request, int $id): CommandeResource
    {
        $commande = $this->mienne($request, $id);

        if (! in_array($commande->statut_commande, ['panier', 'en_attente'], true)) {
            throw new RegleMetierException(
                $commande->statut_commande === 'en_preparation' || $commande->statut_commande === 'prete'
                    ? "Votre commande est déjà en préparation : contactez le restaurant pour l'annuler."
                    : 'Cette commande est terminée : elle ne peut plus être annulée.',
                409,
            );
        }

        return new CommandeResource($this->charger($this->commandes->annuler($commande, $request->user())));
    }

    /**
     * Retrouve une commande de l'étudiant connecté. Une commande qui appartient à quelqu'un d'autre
     * renvoie « introuvable » (404) : on ne révèle même pas qu'elle existe.
     */
    private function mienne(Request $request, int $id): Commande
    {
        return Commande::where('id_etudiant', $request->user()->id_utilisateur)->findOrFail($id);
    }

    /** Recharge la commande avec ce dont l'affichage a besoin (lignes, noms d'articles, journal). */
    private function charger(Commande $commande): Commande
    {
        return $commande->fresh(['lignes.article', 'historique']);
    }
}
