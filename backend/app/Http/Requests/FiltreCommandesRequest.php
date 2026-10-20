<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Paramètres de la liste « mes commandes » : /api/commandes?etat=en_cours&par_page=10 */
class FiltreCommandesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // a_payer = pas encore confirmée · en_cours = confirmée, pas terminée · terminees = servie, livrée ou annulée
            'etat' => ['nullable', Rule::in(['a_payer', 'en_cours', 'terminees'])],
            'par_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
