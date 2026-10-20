<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Rôle du personnel (table `role`) : 'employe', 'gerant' ou 'administrateur'. */
class Role extends Model
{
    protected $table = 'role';

    protected $primaryKey = 'id_role';

    public $timestamps = false;

    protected $fillable = ['libelle_role'];
}
