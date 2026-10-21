<?php

namespace App\Http\Controllers;

use App\Contracts\PaiementGateway;
use App\Http\Resources\CommandeResource;
use App\Models\Commande;
use App\Models\Paiement;
use App\Services\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaiementController extends Controller
{
    public function __construct(private readonly PaiementService $paiements) {}

    public function initier(Request $request, int $commande): JsonResponse
    {
        $donnees = $request->validate([
            'telephone' => ['required', 'string', 'min:8', 'max:20'],
            'operateur' => ['required', 'in:orange_money,mtn_momo'],
        ]);
        $order = Commande::where('id_etudiant', $request->user()->id_utilisateur)->findOrFail($commande);
        $paiement = $this->paiements->initier($order, $request->user(), $donnees['telephone'], $donnees['operateur']);

        return response()->json([
            'paiement' => $paiement->only(['id_paiement', 'montant_paye', 'mode_paiement', 'fournisseur', 'statut_paiement', 'url_paiement']),
            'mode_simulation' => config('services.mobile_money.mode') === 'simulation',
            'commande' => new CommandeResource($order->fresh(['lignes.article', 'historique'])),
        ], 201);
    }

    public function especes(Request $request, int $commande): JsonResponse
    {
        $order = Commande::where('id_etudiant', $request->user()->id_utilisateur)->findOrFail($commande);
        $paiement = $this->paiements->initierEspeces($order, $request->user());
        return response()->json([
            'paiement' => $paiement->only(['id_paiement', 'montant_paye', 'mode_paiement', 'statut_paiement']),
            'commande' => new CommandeResource($order->fresh(['lignes.article', 'historique'])),
        ], 201);
    }

    /** Notification signée : aucune donnée de statut non authentifiée n'est acceptée. */
    public function notification(Request $request, PaiementGateway $gateway): JsonResponse
    {
        // CinetPay peut sonder l'URL de notification sans payload avant d'envoyer un vrai callback.
        if ($request->isMethod('GET') && $request->all() === []) {
            return response()->json(['status' => 'ok']);
        }
        $corps = $request->getContent();
        $signature = $request->header('X-Token') ?? $request->header('X-MMM-Signature');
        abort_unless($gateway->verifierNotification($corps, $signature), 401, 'Signature de paiement invalide.');
        $donnees = Validator::make($gateway->normaliserNotification($request->all()), [
            'reference' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:success,failed,pending'],
            'amount' => ['required', 'integer', 'min:1'],
            'provider_reference' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $paiement = $this->paiements->traiterNotification($donnees);
        return response()->json(['id_paiement' => $paiement->id_paiement, 'statut' => $paiement->statut_paiement]);
    }

    /** Confirmation de simulation seulement en environnement local/testing. */
    public function simuler(Request $request, int $paiement): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']) && config('services.mobile_money.mode') === 'simulation', 404);
        $donnees = $request->validate(['status' => ['required', 'in:success,failed']]);
        $payment = Paiement::whereHas('commande', fn ($q) => $q->where('id_etudiant', $request->user()->id_utilisateur))
            ->findOrFail($paiement);
        $resultat = $this->paiements->traiterNotification([
            'reference' => $payment->cle_idempotence,
            'status' => $donnees['status'],
            'amount' => $payment->montant_paye,
            'provider_reference' => $payment->reference_transaction,
        ]);
        return response()->json(['id_paiement' => $resultat->id_paiement, 'statut' => $resultat->statut_paiement]);
    }
}
