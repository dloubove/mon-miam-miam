<?php

namespace App\Services;

use App\Contracts\PaiementGateway;
use App\Models\Commande;
use App\Models\Paiement;

class RemboursementService
{
    public function __construct(private readonly PaiementGateway $gateway) {}

    public function rembourserCommande(Commande $commande): void
    {
        $paiements = Paiement::where('id_commande', $commande->id_commande)
            ->where('statut_paiement', 'reussi')->lockForUpdate()->get();
        foreach ($paiements as $paiement) {
            // Le remboursement d'espèces est un geste physique géré par le restaurant.
            if ($paiement->mode_paiement !== 'especes') {
                $this->gateway->rembourser($paiement);
            }
            $paiement->update(['statut_paiement' => 'rembourse']);
        }
    }
}
