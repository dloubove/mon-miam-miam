<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Une ligne du journal des statuts d'une commande (table `historique_commande`). */
class HistoriqueCommande extends Model
{
    protected $table = 'historique_commande';

    protected $primaryKey = 'id_historique';

    public $timestamps = false;

    protected $fillable = ['id_commande', 'statut_commande', 'date_changement', 'id_acteur'];

    protected function casts(): array
    {
        return ['date_changement' => 'datetime'];
    }
}
