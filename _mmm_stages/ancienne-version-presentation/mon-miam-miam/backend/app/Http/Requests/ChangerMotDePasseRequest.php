<?php

namespace App\Http\Requests;

use App\Rules\MotDePasseSolide;
use Illuminate\Foundation\Http\FormRequest;

/** Validation du changement de mot de passe depuis la page profil. */
class ChangerMotDePasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mot_de_passe_actuel' => ['required', 'string'],
            // confirmed : exige un champ « nouveau_mot_de_passe_confirmation » identique.
            // different : on interdit de « changer » pour le même mot de passe.
            'nouveau_mot_de_passe' => ['required', new MotDePasseSolide, 'confirmed', 'different:mot_de_passe_actuel'],
        ];
    }

    public function messages(): array
    {
        return [
            'nouveau_mot_de_passe.confirmed' => 'Les deux mots de passe ne correspondent pas.',
            'nouveau_mot_de_passe.different' => "Le nouveau mot de passe doit être différent de l'actuel.",
        ];
    }
}
