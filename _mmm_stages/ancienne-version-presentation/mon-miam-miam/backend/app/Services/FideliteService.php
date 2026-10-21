<?php

namespace App\Services;

/**
 * Règles du programme de fidélité (valeurs lues dans la table `parametre`, modifiables par l'administrateur) :
 *
 *  - GAIN        : 1 point par tranche de 1000 FCFA payés          (points_par_1000_fcfa)
 *  - UTILISATION : par tranche de 15 points → 1000 FCFA de remise  (points_par_tranche_reduction, fcfa_pour_15_points)
 *  - SEUIL       : nombre minimal de points pour en utiliser       (seuil_min_points_utilisation)
 */
class FideliteService
{
    public function __construct(private readonly ParametreService $parametres) {}

    /**
     * Points à dépenser et réduction obtenue pour une commande.
     * On utilise le MAXIMUM possible : autant de tranches entières que le solde le permet,
     * sans que la remise dépasse le montant de la commande.
     *
     * @return array{0: int, 1: int} [points utilisés, réduction en FCFA]
     */
    public function utilisation(int $sousTotal, int $solde): array
    {
        $tranche = $this->parametres->entierRequis('points_par_tranche_reduction'); // 15 points...
        $remise = $this->parametres->entierRequis('fcfa_pour_15_points');           // ...valent 1000 FCFA
        $seuil = $this->parametres->entierRequis('seuil_min_points_utilisation');

        // Nombre de tranches : limité par le solde (floor = on ne coupe pas une tranche) ET par le montant à payer.
        $tranches = min(intdiv($solde, $tranche), intdiv($sousTotal, $remise));

        // Pas assez de points pour atteindre le seuil minimum : aucune utilisation.
        if ($tranches < 1 || $tranches * $tranche < $seuil) {
            return [0, 0];
        }

        return [$tranches * $tranche, $tranches * $remise];
    }

    /**
     * Valeur en FCFA de points précis (pour contrôler une demande : multiple de la tranche, au-dessus du seuil).
     *
     * @return int|null la réduction, ou null si le nombre de points n'est pas valide
     */
    public function reductionPour(int $points): ?int
    {
        $tranche = $this->parametres->entierRequis('points_par_tranche_reduction');
        $remise = $this->parametres->entierRequis('fcfa_pour_15_points');
        $seuil = $this->parametres->entierRequis('seuil_min_points_utilisation');

        if ($points < $seuil || $points % $tranche !== 0) {
            return null;
        }

        return intdiv($points, $tranche) * $remise;
    }

    /** Points gagnés par tranche de 1000 FCFA (paramètre points_par_1000_fcfa). */
    public function tauxGain(): int
    {
        return $this->parametres->entierRequis('points_par_1000_fcfa');
    }

    /**
     * Points gagnés pour un montant payé : 1 par tranche entière de 1000 FCFA.
     *
     * @param  int|null  $taux  taux déjà lu (évite de relire la base quand on calcule pour beaucoup de commandes)
     */
    public function pointsGagnes(int $montantPaye, ?int $taux = null): int
    {
        return intdiv($montantPaye, 1000) * ($taux ?? $this->tauxGain());
    }
}
