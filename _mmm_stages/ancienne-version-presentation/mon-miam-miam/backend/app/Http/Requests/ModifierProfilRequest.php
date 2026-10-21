<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validation de la modification des informations personnelles. Tous les champs sont optionnels. */
class ModifierProfilRequest extends FormRequest
{
    // Route déjà protégée par auth:sanctum : pas de contrôle supplémentaire ici.
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            // « sometimes » : on ne valide le champ que s'il est envoyé (modification partielle).
            'nom' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'telephone' => ['sometimes', 'required', 'regex:/^[0-9+() -]{7,20}$/'],
            // L'email doit rester unique, mais l'utilisateur peut garder le sien (ignore).
            'email' => ['sometimes', 'required', 'email', 'max:150',
                Rule::unique('utilisateur', 'email')->ignore($this->user()->id_utilisateur, 'id_utilisateur')],
            // Lieu de livraison : ne concerne que les étudiants (ignoré pour le personnel).
            'localisation' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.regex' => 'Le numéro de téléphone est invalide.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ];
    }
}
