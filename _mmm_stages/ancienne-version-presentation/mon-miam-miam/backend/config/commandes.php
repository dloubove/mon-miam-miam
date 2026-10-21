<?php

/*
|--------------------------------------------------------------------------
| Réglages des commandes
|--------------------------------------------------------------------------
*/

return [

    /*
    | Confirmation directe (sans paiement).
    |
    | Tant que le paiement n'est pas branché (étape 5), cette option permet de tester tout le parcours
    | en confirmant soi-même une commande via POST /api/commandes/{id}/confirmer.
    | DOIT rester à false en production : sinon n'importe quel étudiant pourrait commander sans payer.
    */
    'confirmation_directe' => (bool) env('COMMANDES_CONFIRMATION_DIRECTE', false),

    /*
    | Durée (en minutes) pendant laquelle une commande « en attente de paiement » peut encore être confirmée.
    | Passé ce délai les prix et la disponibilité des plats ont pu changer : il faut recommencer.
    */
    'validite_panier_minutes' => 120,

];
