<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création (POST) et modification (PATCH) d'un article.
 * À la création certains champs sont obligatoires ; à la modification ils sont facultatifs
 * (« sometimes » : validé seulement s'il est envoyé).
 *
 * Les données arrivent en multipart/form-data (à cause de la photo).
 */
class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'accès est déjà limité par les middlewares auth:sanctum et role:
    }

    public function rules(): array
    {
        $obligatoire = $this->isMethod('post') ? 'required' : 'sometimes|required';
        // Article en cours de modification (null à la création), pour ne pas se bloquer sur son propre nom.
        $article = $this->route('article');

        return [
            'nom' => [...explode('|', $obligatoire), 'string', 'min:2', 'max:150',
                Rule::unique('article', 'nom_article')->ignore($article?->id_article, 'id_article')],
            'description' => ['nullable', 'string', 'max:1000'],
            'prix' => [...explode('|', $obligatoire), 'integer', 'min:1', 'max:1000000'],
            'categorie_id' => [...explode('|', $obligatoire), 'integer', 'exists:categorie,id_categorie'],
            // Photo : JPG, PNG ou WebP, 2 Mo maximum.
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'supprimer_image' => ['sometimes', 'boolean'],
            'disponible' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.unique' => 'Un article porte déjà ce nom.',
            'prix.min' => 'Le prix doit être supérieur à 0.',
            'categorie_id.exists' => "Cette catégorie n'existe pas.",
            'image.mimes' => "L'image doit être au format JPG, PNG ou WebP.",
            'image.max' => "L'image ne doit pas dépasser 2 Mo.",
        ];
    }
}
