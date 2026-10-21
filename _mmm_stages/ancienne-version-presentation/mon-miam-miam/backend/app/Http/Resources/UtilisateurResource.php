<?php

namespace App\Http\Resources;

use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON d'un utilisateur envoyée au frontend.
 * Elle choisit précisément les champs exposés : jamais le mot de passe.
 *
 * @mixin Utilisateur
 */
class UtilisateurResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // On charge les profils en une fois (évite plusieurs petites requêtes SQL).
        $this->resource->loadMissing(['etudiant', 'personnel.role']);

        return [
            'id' => $this->id_utilisateur,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            // 'etudiant', 'employe', 'gerant' ou 'administrateur' : le frontend s'en sert pour router.
            'role' => $this->role,
            'statut' => $this->statut_compte,
            'date_inscription' => $this->date_inscription?->toIso8601String(),

            // when(...) : ce bloc n'apparaît que pour les étudiants.
            'etudiant' => $this->when($this->etudiant, fn () => [
                'code_parrainage' => $this->etudiant->code_parrainage,
                'solde_points' => $this->etudiant->solde_points,
                'localisation' => $this->etudiant->localisation,
                'nombre_filleuls' => $this->etudiant->filleuls()->count(),
            ]),

            // Et celui-ci uniquement pour le personnel.
            'personnel' => $this->when($this->personnel, fn () => [
                'matricule' => $this->personnel->matricule,
                'date_embauche' => $this->personnel->date_embauche?->toDateString(),
            ]),
        ];
    }
}
