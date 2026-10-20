<?php

namespace App\Http\Requests;

use App\Rules\MotDePasseSolide;
use Illuminate\Foundation\Http\FormRequest;

/** Validation du formulaire « nouveau mot de passe » ouvert depuis le lien reçu par email. */
class ReinitialiserMotDePasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'jeton' => ['required', 'string'],        // le jeton contenu dans le lien
            'mot_de_passe' => ['required', new MotDePasseSolide, 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return ['mot_de_passe.confirmed' => 'Les deux mots de passe ne correspondent pas.'];
    }
}
