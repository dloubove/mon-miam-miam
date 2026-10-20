<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Création (POST) et modification (PATCH) d'une promotion. */
class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $obligatoire = $this->isMethod('post') ? ['required'] : ['sometimes', 'required'];

        return [
            'titre' => [...$obligatoire, 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            // 1 à 100 % (0 % n'aurait aucun effet).
            'taux' => [...$obligatoire, 'numeric', 'between:1,100'],
            'date_debut' => [...$obligatoire, 'date_format:Y-m-d'],
            // after_or_equal : la fin ne peut pas précéder le début (vérifié aussi côté base).
            'date_fin' => [...$obligatoire, 'date_format:Y-m-d', 'after_or_equal:date_debut'],
            'articles' => ['sometimes', 'array'],
            'articles.*' => ['integer', 'distinct', 'exists:article,id_article'],
            'affiche' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'taux.between' => 'Le taux doit être compris entre 1 et 100 %.',
            'date_debut.date_format' => 'La date de début doit être au format AAAA-MM-JJ.',
            'date_fin.date_format' => 'La date de fin doit être au format AAAA-MM-JJ.',
            'date_fin.after_or_equal' => 'La date de fin ne peut pas précéder la date de début.',
            'articles.*.exists' => "Un des articles n'existe pas.",
        ];
    }
}
