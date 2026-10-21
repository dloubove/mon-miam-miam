<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Promotion temporaire (table `promotion`) : un pourcentage de réduction sur certains articles. */
class Promotion extends Model
{
    protected $table = 'promotion';

    protected $primaryKey = 'id_promotion';

    public $timestamps = false;

    protected $fillable = [
        'titre_promotion', 'description_promotion', 'taux_reduction',
        'date_debut_promo', 'date_fin_promo', 'affiche_promo',
    ];

    protected function casts(): array
    {
        return [
            'taux_reduction' => 'float',
            'date_debut_promo' => 'date',
            'date_fin_promo' => 'date',
        ];
    }

    /** Les articles concernés, via la table de liaison `concerner`. */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'concerner', 'id_promotion', 'id_article');
    }

    /** Filtre SQL : promotions en cours aujourd'hui (bornes comprises). */
    public function scopeActives(Builder $requete): Builder
    {
        $aujourdhui = now()->toDateString();

        return $requete->where('date_debut_promo', '<=', $aujourdhui)->where('date_fin_promo', '>=', $aujourdhui);
    }

    /** Même test, mais en PHP sur un objet déjà chargé. */
    public function estActive(): bool
    {
        $aujourdhui = now()->toDateString();

        return $this->date_debut_promo->toDateString() <= $aujourdhui && $this->date_fin_promo->toDateString() >= $aujourdhui;
    }
}
