<?php

namespace App\Http\Requests;

use App\Rules\MotDePasseSolide;
use Illuminate\Foundation\Http\FormRequest;

/** Validation du formulaire « Créer un compte » du frontend. */
class InscriptionRequest extends FormRequest
{
    // L'inscription est publique : tout le monde a le droit d'essayer.
    public function authorize(): bool
    {
        return true;
    }

    /** Nettoyage AVANT validation : email en minuscules, code de parrainage en majuscules. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'code_parrain' => $this->filled('code_parrain') ? strtoupper(trim($this->input('code_parrain'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            // Mêmes caractères que la contrainte de la base : chiffres, +, espaces, tirets, parenthèses.
            'telephone' => ['required', 'regex:/^[0-9+() -]{7,20}$/'],
            // unique:utilisateur,email → refuse un email déjà inscrit.
            'email' => ['required', 'email', 'max:150', 'unique:utilisateur,email'],
            'localisation' => ['required', 'string', 'max:255'],
            'mot_de_passe' => ['required', new MotDePasseSolide],
            // Facultatif ; s'il est fourni, il doit exister dans la table `etudiant`.
            'code_parrain' => ['nullable', 'string', 'exists:etudiant,code_parrainage'],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.regex' => 'Le numéro de téléphone est invalide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'code_parrain.exists' => "Ce code de parrainage n'existe pas.",
        ];
    }
}
