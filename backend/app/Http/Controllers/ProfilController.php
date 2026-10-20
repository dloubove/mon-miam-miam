<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangerMotDePasseRequest;
use App\Http\Requests\ModifierProfilRequest;
use App\Http\Resources\UtilisateurResource;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Le compte de l'utilisateur connecté (« moi ») : consulter, modifier, changer de mot de passe, supprimer.
 * Toutes ces routes exigent un jeton Sanctum valide.
 */
class ProfilController extends Controller
{
    /** GET /api/moi : l'utilisateur connecté (pour afficher son profil, ses points, son rôle…). */
    public function show(Request $request): UtilisateurResource
    {
        return new UtilisateurResource($request->user());
    }

    /** PATCH /api/moi : modifie nom, email, téléphone et (étudiants) lieu de livraison. */
    public function update(ModifierProfilRequest $request): UtilisateurResource
    {
        $utilisateur = $request->user();
        $donnees = $request->validated();
        $emailChange = isset($donnees['email']) && $donnees['email'] !== $utilisateur->email;

        // Les deux modifications réussissent ensemble ou pas du tout.
        DB::transaction(function () use ($utilisateur, $donnees) {
            // Arr::only : on ne garde que les colonnes de la table `utilisateur`.
            $modifications = Arr::only($donnees, ['nom', 'email', 'telephone']);
            if (isset($modifications['email']) && $modifications['email'] !== $utilisateur->email) {
                $modifications['email_verified_at'] = null;
            }
            $utilisateur->update($modifications);

            // La localisation est dans la table `etudiant` : on ne la touche que pour un étudiant.
            if (array_key_exists('localisation', $donnees) && $utilisateur->etudiant) {
                $utilisateur->etudiant->update(['localisation' => $donnees['localisation']]);
            }
        });

        if ($emailChange) {
            app(\App\Services\AuthService::class)->envoyerLienVerification($utilisateur->fresh());
        }

        // fresh() recharge les données depuis la base pour renvoyer l'état exact.
        return new UtilisateurResource($utilisateur->fresh());
    }

    /** PUT /api/moi/mot-de-passe : change le mot de passe en connaissant l'ancien. */
    public function changerMotDePasse(ChangerMotDePasseRequest $request)
    {
        $utilisateur = $request->user();

        // On exige l'ancien mot de passe : un jeton volé ne suffit pas à prendre le compte.
        if (! Hash::check($request->input('mot_de_passe_actuel'), $utilisateur->mot_de_passe)) {
            throw ValidationException::withMessages(['mot_de_passe_actuel' => ['Le mot de passe actuel est incorrect.']]);
        }

        $utilisateur->update(['mot_de_passe' => $request->input('nouveau_mot_de_passe')]);

        // Tous les AUTRES appareils sont déconnectés ; celui-ci reste connecté.
        $jetonCourant = $utilisateur->currentAccessToken();
        if ($jetonCourant instanceof PersonalAccessToken) {
            $utilisateur->tokens()->whereKeyNot($jetonCourant->getKey())->delete();
        }

        return response()->json(['message' => 'Votre mot de passe a été modifié.']);
    }

    /**
     * DELETE /api/moi : suppression du compte (droit à l'effacement).
     *
     * On ne supprime pas la ligne : les commandes passées doivent rester pour la comptabilité
     * (la base l'interdit d'ailleurs : clés étrangères en RESTRICT). On ANONYMISE à la place :
     * les données personnelles sont effacées et le compte est désactivé.
     */
    public function supprimer(Request $request): Response
    {
        $request->validate(['mot_de_passe' => ['required', 'string']]);
        $utilisateur = $request->user();

        if (! Hash::check($request->input('mot_de_passe'), $utilisateur->mot_de_passe)) {
            throw ValidationException::withMessages(['mot_de_passe' => ['Le mot de passe est incorrect.']]);
        }

        DB::transaction(function () use ($utilisateur) {
            $utilisateur->update([
                'nom' => 'Compte supprimé',
                // L'email doit rester unique : on le remplace par une adresse factice propre à ce compte.
                'email' => "supprime-{$utilisateur->id_utilisateur}@invalide.local",
                'telephone' => '0000000',
                'statut_compte' => 'inactif',
                // Mot de passe aléatoire inconnu de tous : plus aucune connexion possible.
                'mot_de_passe' => Str::random(40),
            ]);

            $utilisateur->etudiant?->update(['localisation' => null]);

            // On coupe toutes les connexions, y compris celle-ci.
            $utilisateur->tokens()->delete();
        });

        return response()->noContent();
    }
}
