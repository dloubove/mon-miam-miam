<?php

namespace App\Contracts;

use App\Models\Paiement;

interface PaiementGateway
{
    /** Démarre un paiement auprès de l'agrégateur et retourne ses données d'affichage. */
    public function initier(Paiement $paiement, string $telephone, string $operateur): array;

    /** Vérifie l'authenticité d'une notification fournisseur. */
    public function verifierNotification(string $corps, ?string $signature): bool;

    /** Convertit un callback fournisseur vérifié en données métier normalisées. */
    public function normaliserNotification(array $payload): array;

    /** Demande le remboursement au même opérateur que celui qui a encaissé le paiement. */
    public function rembourser(Paiement $paiement): void;
}
