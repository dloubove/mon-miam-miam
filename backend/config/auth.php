<?php

/* Configuration de l'authentification. Les routes API utilisent Sanctum. */

use App\Models\Utilisateur;

return [

    'defaults' => [
        // Garde utilisé par défaut quand on écrit `auth()->user()` ou le middleware `auth`.
        'guard' => 'web',
        // Courtier de réinitialisation de mot de passe : non utilisé (on gère nous-mêmes
        // la table `jeton_mdp`), mais la clé doit exister pour Laravel.
        'passwords' => 'utilisateurs',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'utilisateurs',
        ],
    ],

    'providers' => [
        // Indique à Laravel où chercher les comptes : le modèle Eloquent `Utilisateur`
        // (table `utilisateur`, colonne de mot de passe `mot_de_passe`).
        'utilisateurs' => [
            'driver' => 'eloquent',
            'model' => Utilisateur::class,
        ],
    ],

    'passwords' => [
        'utilisateurs' => [
            'provider' => 'utilisateurs',
            'table' => 'jeton_mdp',
            'expire' => 60, // minutes : durée de validité d'un lien de réinitialisation
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
