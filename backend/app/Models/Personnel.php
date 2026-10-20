<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profil du personnel (table `personnel`) : employé, gérant ou administrateur.
 */
class Personnel extends Model
{
    protected $table = 'personnel';

    protected $primaryKey = 'id_utilisateur';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_utilisateur', 'matricule', 'date_embauche', 'id_role', 'id_restaurant', 'id_createur',
    ];

    protected function casts(): array
    {
        return ['date_embauche' => 'date'];
    }

    /** Pendant de la règle de Etudiant : un étudiant ne peut pas devenir membre du personnel. */
    protected static function booted(): void
    {
        static::creating(function (Personnel $personnel) {
            if (Etudiant::where('id_utilisateur', $personnel->id_utilisateur)->exists()) {
                throw new DomainException('Cet utilisateur est déjà étudiant : il ne peut pas rejoindre le personnel.');
            }
        });
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }
}
