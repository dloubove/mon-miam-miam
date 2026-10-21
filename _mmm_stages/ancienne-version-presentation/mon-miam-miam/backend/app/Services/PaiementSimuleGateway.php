<?php

namespace App\Services;

use App\Contracts\PaiementGateway;
use App\Models\Paiement;

/** Adaptateur local : aucun appel à Orange Money ou MTN n'est effectué. */
class PaiementSimuleGateway implements PaiementGateway
{
    public function initier(Paiement $paiement, string $telephone, string $operateur): array
    {
        return [
            'reference' => 'SIM-'.$paiement->id_paiement,
            'url' => null,
            'message' => 'Paiement simulé : utilisez la route de confirmation de test.',
        ];
    }

    public function verifierNotification(string $corps, ?string $signature): bool
    {
        $secret = (string) config('services.mobile_money.webhook_secret');
        if ($secret === '' || $signature === null) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $corps, $secret), $signature);
    }

    public function normaliserNotification(array $payload): array
    {
        return $payload;
    }

    public function rembourser(Paiement $paiement): void
    {
        // Le test local enregistre l'état, sans mouvement d'argent réel.
    }
}
