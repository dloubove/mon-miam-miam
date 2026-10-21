<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $table = 'paiement';
    protected $primaryKey = 'id_paiement';
    public $timestamps = false;

    protected $fillable = [
        'montant_paye', 'mode_paiement', 'fournisseur', 'reference_transaction',
        'cle_idempotence', 'url_paiement', 'reponse_fournisseur', 'statut_paiement',
        'date_paiement', 'id_commande',
    ];

    protected function casts(): array
    {
        return ['montant_paye' => 'integer', 'date_paiement' => 'datetime', 'reponse_fournisseur' => 'array'];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }
}
