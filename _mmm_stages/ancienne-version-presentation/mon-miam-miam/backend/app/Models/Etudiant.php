<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil étudiant (table `etudiant`) : points de fidélité, code de parrainage, localisation.
 * Sa clé primaire est aussi une clé étrangère vers `utilisateur`.
 */
class Etudiant extends Model
{
    protected $table = 'etudiant';

    protected $primaryKey = 'id_utilisateur';

    // La clé n'est pas auto-générée ici : elle reprend l'id de l'utilisateur.
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_utilisateur', 'code_parrainage', 'solde_points', 'localisation',
        'id_parrain', 'date_parrainage', 'points_parrainage', 'recompense_attribuee',
    ];

    protected function casts(): array
    {
        return [
            'date_parrainage' => 'datetime',
            'recompense_attribuee' => 'boolean',
        ];
    }

    /**
     * Règle métier : un utilisateur est SOIT étudiant, SOIT membre du personnel.
     * La base ne peut pas l'exprimer avec une simple contrainte : on la vérifie ici,
     * avant chaque création de profil étudiant.
     */
    protected static function booted(): void
    {
        static::creating(function (Etudiant $etudiant) {
            if (Personnel::where('id_utilisateur', $etudiant->id_utilisateur)->exists()) {
                throw new DomainException('Cet utilisateur fait déjà partie du personnel : il ne peut pas être étudiant.');
            }
        });
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /** L'étudiant qui a parrainé celui-ci (s'il y en a un). */
    public function parrain(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_parrain', 'id_utilisateur');
    }

    /** Les étudiants qui se sont inscrits avec son code de parrainage. */
    public function filleuls(): HasMany
    {
        return $this->hasMany(self::class, 'id_parrain', 'id_utilisateur');
    }
}
