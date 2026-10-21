<?php

namespace App\Services;

use App\Contracts\PaiementGateway;
use App\Models\Paiement;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Intégration CinetPay Checkout v2 (page de paiement hébergée par CinetPay). */
class PaiementCinetPayGateway implements PaiementGateway
{
    public function initier(Paiement $paiement, string $telephone, string $operateur): array
    {
        [$apiKey, $siteId] = $this->identifiants();
        if ($paiement->montant_paye % 5 !== 0) {
            throw new RuntimeException('CinetPay exige un montant qui est un multiple de 5 FCFA.');
        }

        // CinetPay limite transaction_id à 30 caractères ; la clé UUID interne reste indépendante.
        $reference = 'MMM'.strtoupper(substr(hash('sha256', $paiement->cle_idempotence), 0, 24));
        $commande = $paiement->commande;
        $reponse = Http::acceptJson()->timeout(20)->post('https://api-checkout.cinetpay.com/v2/payment', [
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $reference,
            'amount' => $paiement->montant_paye,
            'currency' => config('services.mobile_money.currency', 'XAF'),
            'description' => 'Commande Mon Miam Miam #'.$commande->id_commande,
            'notify_url' => url('/api/paiements/notification'),
            'return_url' => rtrim(config('app.frontend_url', config('app.url')), '/').'/mon-espace',
            'channels' => 'MOBILE_MONEY',
            'metadata' => json_encode([
                'paiement_id' => $paiement->id_paiement,
                'telephone' => $telephone,
                'operateur_prefere' => $operateur,
            ], JSON_UNESCAPED_UNICODE),
        ])->throw()->json();

        if ((string) ($reponse['code'] ?? '') !== '201' || empty($reponse['data']['payment_url'])) {
            throw new RuntimeException($reponse['description'] ?? 'CinetPay n’a pas fourni de page de paiement.');
        }

        return [
            'reference' => $reference,
            'url' => $reponse['data']['payment_url'],
            'message' => 'Redirection vers le paiement sécurisé CinetPay.',
            'raw' => $reponse,
        ];
    }

    public function verifierNotification(string $corps, ?string $signature): bool
    {
        $secret = (string) config('services.cinetpay.secret_key');
        if ($secret === '' || $signature === null) return false;

        parse_str($corps, $payload);
        $champs = [
            'cpm_site_id', 'cpm_trans_id', 'cpm_trans_date', 'cpm_amount', 'cpm_currency',
            'signature', 'payment_method', 'cel_phone_num', 'cpm_phone_prefixe', 'cpm_language',
            'cpm_version', 'cpm_payment_config', 'cpm_page_action', 'cpm_custom',
            'cpm_designation', 'cpm_error_message',
        ];
        $message = '';
        foreach ($champs as $champ) $message .= (string) ($payload[$champ] ?? '');
        return hash_equals(hash_hmac('sha256', $message, $secret), $signature);
    }

    public function normaliserNotification(array $payload): array
    {
        [$apiKey, $siteId] = $this->identifiants();
        $reference = (string) ($payload['cpm_trans_id'] ?? '');
        if ($reference === '' || (string) ($payload['cpm_site_id'] ?? '') !== $siteId) {
            throw new RuntimeException('Notification CinetPay sans référence ou site valide.');
        }

        // Ne jamais faire confiance au statut du formulaire de notification : interrogation serveur-à-serveur.
        $verification = Http::acceptJson()->timeout(15)->post('https://api-checkout.cinetpay.com/v2/payment/check', [
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $reference,
        ])->throw()->json();

        if (($verification['code'] ?? null) !== '00' || ! isset($verification['data'])) {
            throw new RuntimeException('CinetPay ne confirme pas encore cette transaction.');
        }
        $data = $verification['data'];
        if ((string) ($data['currency'] ?? '') !== config('services.mobile_money.currency', 'XAF')) {
            throw new RuntimeException('La devise confirmée par CinetPay est incorrecte.');
        }

        $status = strtoupper((string) ($data['status'] ?? ''));
        $normalisedStatus = match ($status) {
            'ACCEPTED' => 'success',
            'REFUSED', 'CANCELLED', 'CANCELED' => 'failed',
            'WAITING_FOR_CUSTOMER', 'PENDING' => 'pending',
            default => throw new RuntimeException('Statut CinetPay inconnu : '.$status),
        };

        return [
            'reference' => $reference,
            'status' => $normalisedStatus,
            'amount' => (int) ($data['amount'] ?? 0),
            'provider_reference' => $reference,
        ];
    }

    public function rembourser(Paiement $paiement): void
    {
        throw new RuntimeException('Le remboursement CinetPay doit être traité via le portail marchand.');
    }

    private function identifiants(): array
    {
        $apiKey = (string) config('services.cinetpay.api_key');
        $siteId = (string) config('services.cinetpay.site_id');
        if ($apiKey === '' || $siteId === '') {
            throw new RuntimeException('Configurez CINETPAY_API_KEY et CINETPAY_SITE_ID dans le fichier .env.');
        }
        return [$apiKey, $siteId];
    }
}
