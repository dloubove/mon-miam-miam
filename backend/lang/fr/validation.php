<?php

/*
|--------------------------------------------------------------------------
| Messages de validation en français
|--------------------------------------------------------------------------
| :attribute est remplacé par le nom du champ (voir « attributes » en bas).
*/

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'array' => 'Le champ :attribute doit être une liste.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'date' => "Le champ :attribute n'est pas une date valide.",
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'exists' => 'La valeur du champ :attribute est invalide.',
    'in' => 'La valeur du champ :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'same' => 'Les champs :attribute et :other doivent être identiques.',
    'string' => 'Le champ :attribute doit être du texte.',
    'unique' => 'Cette valeur du champ :attribute est déjà utilisée.',

    // Les règles « taille » ont un message différent selon le type de valeur.
    'max' => [
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max éléments.',
    ],
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
    ],

    'custom' => [],

    // Noms lisibles des champs affichés dans les messages.
    'attributes' => [
        'nom' => 'nom',
        'email' => 'email',
        'telephone' => 'téléphone',
        'localisation' => 'lieu de livraison',
        'mot_de_passe' => 'mot de passe',
        'mot_de_passe_actuel' => 'mot de passe actuel',
        'nouveau_mot_de_passe' => 'nouveau mot de passe',
        'code_parrain' => 'code de parrainage',
        'jeton' => 'lien de réinitialisation',
    ],
];
