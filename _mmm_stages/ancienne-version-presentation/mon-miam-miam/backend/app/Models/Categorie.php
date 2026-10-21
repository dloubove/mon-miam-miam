<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catégorie d'articles (table `categorie`) : plat, snack, boisson, dessert. */
class Categorie extends Model
{
    protected $table = 'categorie';

    protected $primaryKey = 'id_categorie';

    public $timestamps = false;

    protected $fillable = ['libelle_categorie'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'id_categorie', 'id_categorie');
    }
}
