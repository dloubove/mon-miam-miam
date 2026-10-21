<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation du formulaire de connexion. */
class ConnexionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Les emails sont stockés en minuscules : on normalise pour que « Dan@X.com » fonctionne.
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'mot_de_passe' => ['required', 'string'],
            // Case « Se souvenir de moi » : prolonge la durée de la connexion.
            'se_souvenir' => ['sometimes', 'boolean'],
        ];
    }
}
