<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Menu d'une journée (table `menu_jour`) : la liste des articles proposés à la vente ce jour-là.
 * Un seul menu par jour et par restaurant (contrainte d'unicité en base).
 */
class MenuJour extends Model
{
    protected $table = 'menu_jour';

    protected $primaryKey = 'id_menu_jour';

    public $timestamps = false;

    protected $fillable = ['date_menu', 'id_restaurant'];

    protected function casts(): array
    {
        return ['date_menu' => 'date'];
    }

    /** Les articles du menu, via la table de liaison `composer`. */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'composer', 'id_menu_jour', 'id_article');
    }
}
