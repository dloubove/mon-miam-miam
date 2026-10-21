<?php

namespace App\Services;

use App\Contracts\PaiementGateway;
use App\Models\Paiement;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Client d'un agrégateur configuré avec le contrat REST générique décrit dans la documentation. */
class PaiementAgregateurGateway implements PaiementGateway
{
    public function initier(Paiement $paiement, string $telephone, string $operateur): array
    {
        $url = config('services.mobile_money.url');
        $cle = config('services.mobile_money.key');
        if (! $url || ! $cle) throw new RuntimeException('L’agrégateur Mobile Money n’est pas configuré.');

        $reponse = Http::withToken($cle)->acceptJson()->timeout(15)->post(rtrim($url, '/').'/payments', [
            'reference' => $paiement->cle_idempotence,
            'amount' => $paiement->montant_paye,
            'currency' => config('services.mobile_money.currency', 'XAF'),
            'operator' => $operateur,
            'phone' => $telephone,
            'callback_url' => url('/api/paiements/notification'),
        ])->throw()->json();

        return [
            'reference' => (string) ($reponse['reference'] ?? $paiement->cle_idempotence),
            'url' => $reponse['payment_url'] ?? null,
            'message' => $reponse['message'] ?? 'Paiement initié.',
            'raw' => $reponse,
        ];
    }

    public function verifierNotification(string $corps, ?string $signature): bool
    {
        $secret = (string) config('services.mobile_money.webhook_secret');
        return $secret !== '' && $signature !== null
            && hash_equals(hash_hmac('sha256', $corps, $secret), $signature);
    }

    public function normaliserNotification(array $payload): array
    {
        return $payload;
    }

    public function rembourser(Paiement $paiement): void
    {
        $url = config('services.mobile_money.url');
        $cle = config('services.mobile_money.key');
        if (! $url || ! $cle || ! $paiement->reference_transaction) {
            throw new RuntimeException('Impossible de demander le remboursement à l’agrégateur.');
        }
        Http::withToken($cle)->acceptJson()->timeout(15)
            ->post(rtrim($url, '/').'/payments/'.$paiement->reference_transaction.'/refund', [
                'reference' => 'REFUND-'.$paiement->cle_idempotence,
                'amount' => $paiement->montant_paye,
                'currency' => config('services.mobile_money.currency', 'XAF'),
            ])->throw();
    }
}
