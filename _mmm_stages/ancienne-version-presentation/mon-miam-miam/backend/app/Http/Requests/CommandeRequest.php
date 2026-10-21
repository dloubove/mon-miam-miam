<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Données d'une commande (devis et création) : le contenu du panier et les choix de la page « Où et quand ? ».
 */
class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // accès déjà limité aux étudiants par le middleware de la route
    }

    /**
     * En livraison, si le frontend n'envoie pas d'adresse, on utilise le lieu de livraison habituel de l'étudiant
     * (la page profil dit : « on pré-remplira ton panier avec ces informations »).
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('mode_service') === 'livraison' && ! $this->filled('adresse_livraison')) {
            $this->merge(['adresse_livraison' => $this->user()?->etudiant?->localisation]);
        }
    }

    public function rules(): array
    {
        return [
            // Le panier : de 1 à 20 lignes.
            'lignes' => ['required', 'array', 'min:1', 'max:20'],
            // distinct : un même article ne peut pas apparaître deux fois (le frontend additionne les quantités).
            'lignes.*.article_id' => ['required', 'integer', 'distinct'],
            'lignes.*.quantite' => ['required', 'integer', 'between:1,20'],

            'mode_service' => ['required', Rule::in(['sur_place', 'livraison'])],
            // Heure d'arrivée au format 14:30 : obligatoire sur place, facultative en livraison.
            'heure_arrivee' => ['required_if:mode_service,sur_place', 'nullable', 'date_format:H:i'],
            'adresse_livraison' => ['required_if:mode_service,livraison', 'nullable', 'string', 'max:255'],

            'commentaire' => ['nullable', 'string', 'max:500'],
            'utiliser_points' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Votre panier est vide.',
            'lignes.min' => 'Votre panier est vide.',
            'lignes.max' => 'Un panier ne peut pas contenir plus de 20 articles différents.',
            'lignes.*.quantite.between' => 'La quantité doit être comprise entre 1 et 20.',
            'lignes.*.article_id.distinct' => 'Un article est présent plusieurs fois dans le panier.',
            'heure_arrivee.required_if' => "Indiquez l'heure à laquelle vous arrivez.",
            'heure_arrivee.date_format' => "L'heure doit être au format HH:MM (ex. 12:30).",
            'adresse_livraison.required_if' => "Indiquez l'adresse de livraison (ou renseignez votre lieu habituel dans votre profil).",
        ];
    }
}
