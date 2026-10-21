<?php

namespace Tests\Concerns;

use App\Models\Utilisateur;

/**
 * Outils partagés par les tests d'authentification.
 */
trait GereLesConnexions
{
    /** Mot de passe de tous les comptes de démonstration (voir DemoSeeder). */
    protected string $motDePasse = 'Demo1234';

    /** Retourne le compte de démonstration ayant cet email. */
    protected function compte(string $email): Utilisateur
    {
        return Utilisateur::where('email', $email)->firstOrFail();
    }

    /** Crée directement un jeton pour ce compte et renvoie le jeton EN CLAIR. */
    protected function jetonPour(string $email): string
    {
        return $this->compte($email)->createToken('test', ['*'], now()->addDay())->plainTextToken;
    }

    /** Équivalent d'une requête du frontend connecté : ajoute « Authorization: Bearer <jeton> ». */
    protected function enTantQue(string $email): static
    {
        return $this->withToken($this->jetonPour($email));
    }
}
