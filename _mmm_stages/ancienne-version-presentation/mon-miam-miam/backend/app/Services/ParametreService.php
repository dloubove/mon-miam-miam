<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Lecture des paramètres modifiables du restaurant (table `parametre`) :
 * règles de fidélité, points de parrainage, etc.
 */
class ParametreService
{
    /** Valeur brute d'un paramètre, ou $defaut s'il n'existe pas. */
    public function valeur(string $cle, ?string $defaut = null): ?string
    {
        return DB::table('parametre')->where('cle_parametre', $cle)->value('valeur_parametre') ?? $defaut;
    }

    /** Valeur d'un paramètre convertie en entier. */
    public function entier(string $cle, int $defaut = 0): int
    {
        return (int) $this->valeur($cle, (string) $defaut);
    }

    /**
     * Valeur entière strictement positive d'un paramètre obligatoire (règles de fidélité).
     * Contrairement à entier(), il n'y a pas de valeur par défaut : un paramètre absent ou invalide
     * est une erreur de configuration, qu'on refuse de masquer (sinon on calculerait des réductions fausses).
     */
    public function entierRequis(string $cle): int
    {
        $valeur = $this->valeur($cle);

        if ($valeur === null || ! ctype_digit($valeur) || (int) $valeur <= 0) {
            throw new RuntimeException("Paramètre « {$cle} » absent ou invalide dans la table parametre.");
        }

        return (int) $valeur;
    }
}
