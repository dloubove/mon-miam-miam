<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Compte de connexion (table `utilisateur`).
 *
 * Tout le monde (étudiant, employé, gérant, administrateur) a une ligne ici.
 * Le « profil » métier est dans `etudiant` OU dans `personnel` (jamais les deux).
 */
class Utilisateur extends Authenticatable
{
    use HasApiTokens;
    // La table et la clé primaire ne suivent pas les conventions anglaises de Laravel
    // (`utilisateurs` / `id`) : on les indique explicitement.
    protected $table = 'utilisateur';

    protected $primaryKey = 'id_utilisateur';

    // La table n'a pas les colonnes created_at / updated_at (elle a `date_inscription`).
    public $timestamps = false;

    // Colonnes qu'on a le droit de remplir en masse (Utilisateur::create([...])).
    protected $fillable = ['nom', 'email', 'mot_de_passe', 'telephone', 'statut_compte', 'email_verified_at'];

    // Colonnes jamais incluses quand le modèle est converti en JSON : le hash ne doit jamais sortir.
    protected $hidden = ['mot_de_passe'];

    protected function casts(): array
    {
        return [
            // « hashed » : toute valeur affectée à mot_de_passe est hachée (bcrypt) automatiquement.
            'mot_de_passe' => 'hashed',
            'date_inscription' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    /** Laravel lit le mot de passe dans la colonne `mot_de_passe` (et non `password`). */
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    /** Profil étudiant (existe seulement pour les étudiants). */
    public function etudiant(): HasOne
    {
        return $this->hasOne(Etudiant::class, 'id_utilisateur', 'id_utilisateur');
    }

    /** Profil personnel (existe seulement pour employé, gérant, administrateur). */
    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Rôle applicatif : 'etudiant', 'employe', 'gerant' ou 'administrateur'.
     * Utilisable comme une propriété : $utilisateur->role
     */
    public function getRoleAttribute(): ?string
    {
        if ($this->etudiant) {
            return 'etudiant';
        }

        // Pour le personnel, le rôle vient de la table `role`.
        return $this->personnel?->role?->libelle_role;
    }

    /** Un compte « inactif » (désactivé ou supprimé) ne peut plus se connecter. */
    public function estActif(): bool
    {
        return $this->statut_compte === 'actif';
    }
}
