<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Paramètres d'URL des listes d'articles : /api/menu-du-jour?type=plats&q=poulet&prix_max=3000&tri=prix_asc
 * Tous sont facultatifs.
 */
class FiltreArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la liste est publique
    }

    /** Dans une URL, « true » et « false » arrivent en texte : on les convertit en vrais booléens. */
    protected function prepareForValidation(): void
    {
        foreach (['disponible', 'promo'] as $champ) {
            if ($this->has($champ)) {
                $this->merge([$champ => filter_var($this->input($champ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['plats', 'boissons'])],
            'categorie' => ['nullable', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:100'],
            'prix_min' => ['nullable', 'integer', 'min:0'],
            // gte : le maximum ne peut pas être inférieur au minimum. On ne l'applique que si un minimum
            // est fourni : sans cela, « prix_max » seul serait rejeté à tort.
            'prix_max' => ['nullable', 'integer', 'min:0', ...($this->filled('prix_min') ? ['gte:prix_min'] : [])],
            'disponible' => ['nullable', 'boolean'],
            'promo' => ['nullable', 'boolean'],
            'tri' => ['nullable', Rule::in(['defaut', 'prix_asc', 'prix_desc', 'nom'])],
            // 50 maximum : empêche de demander tout le catalogue d'un coup.
            'par_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }

    public function messages(): array
    {
        return [
            'prix_max.gte' => 'Le prix maximum doit être supérieur ou égal au prix minimum.',
            'type.in' => 'Le type doit être « plats » ou « boissons ».',
            'tri.in' => 'Tri inconnu.',
        ];
    }
}
