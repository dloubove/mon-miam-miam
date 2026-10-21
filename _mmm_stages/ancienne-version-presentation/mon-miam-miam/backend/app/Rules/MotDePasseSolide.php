<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Règle du cahier des charges : 8 caractères minimum, au moins 1 majuscule et 1 chiffre. */
class MotDePasseSolide implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // is_string : évite une erreur si on reçoit un tableau ; mb_strlen compte les accents correctement.
        $valide = is_string($value)
            && mb_strlen($value) >= 8
            && preg_match('/[A-Z]/', $value)   // au moins une majuscule
            && preg_match('/\d/', $value);     // au moins un chiffre

        if (! $valide) {
            $fail('Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule et 1 chiffre.');
        }
    }
}
