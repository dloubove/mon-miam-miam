<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Une ligne du « grand livre » des points de fidélité (table `mouvement_points`).
 *
 * Règle d'origine : le grand livre est IMMUABLE. Pour corriger une erreur on ajoute un mouvement
 * correctif ('ajustement'), on ne modifie ni ne supprime jamais une ligne existante.
 * Les événements ci-dessous bloquent la modification et la suppression via Eloquent.
 * (Une requête SQL directe pourrait encore les contourner : c'est une règle applicative.)
 */
class MouvementPoints extends Model
{
    protected $table = 'mouvement_points';

    protected $primaryKey = 'id_mouvement';

    public $timestamps = false;

    protected $fillable = [
        'type_mouvement', 'nombre_points', 'date_mouvement', 'date_expiration_points', 'id_etudiant', 'id_commande',
    ];

    protected function casts(): array
    {
        return ['nombre_points' => 'integer', 'date_mouvement' => 'datetime', 'date_expiration_points' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Le grand livre des points est immuable : ajoutez un mouvement correctif.'));
        static::deleting(fn () => throw new LogicException('Le grand livre des points est immuable : ajoutez un mouvement correctif.'));
    }
}
