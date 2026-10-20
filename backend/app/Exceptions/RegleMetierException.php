<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violation d'une règle métier (ex. « la commande est déjà en préparation », « solde de points insuffisant »).
 *
 * Elle est transformée en réponse JSON { "message": ... } avec le code HTTP indiqué
 * (voir bootstrap/app.php) : 409 = conflit avec l'état actuel, 422 = demande refusée.
 */
class RegleMetierException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statutHttp = 409)
    {
        parent::__construct($message);
    }
}
