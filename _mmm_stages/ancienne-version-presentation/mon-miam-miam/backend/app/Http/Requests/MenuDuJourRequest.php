<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Composition du menu d'aujourd'hui : la liste complète des identifiants d'articles proposés. */
class MenuDuJourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // « present » : le champ doit être envoyé, mais la liste peut être vide (= aucun plat aujourd'hui).
            'articles' => ['present', 'array'],
            // distinct : pas deux fois le même article ; exists : l'article doit exister.
            'articles.*' => ['integer', 'distinct', 'exists:article,id_article'],
        ];
    }

    public function messages(): array
    {
        return [
            'articles.*.exists' => "Un des articles n'existe pas.",
            'articles.*.distinct' => 'Un article est présent plusieurs fois.',
        ];
    }
}
