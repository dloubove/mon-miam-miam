<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamation extends Model
{
    protected $table = 'reclamation';
    protected $primaryKey = 'id_reclamation';
    public $timestamps = false;
    protected $fillable = [
        'objet', 'description_reclamation', 'date_reclamation', 'statut_reclamation', 'id_etudiant',
        'id_commande', 'reponse_proposee', 'date_reponse', 'id_repondant', 'decision_gerant',
        'motif_decision', 'date_decision', 'id_decideur',
    ];
    protected function casts(): array
    {
        return ['date_reclamation' => 'datetime', 'date_reponse' => 'datetime', 'date_decision' => 'datetime'];
    }
    public function etudiant(): BelongsTo { return $this->belongsTo(Etudiant::class, 'id_etudiant', 'id_utilisateur'); }
    public function commande(): BelongsTo { return $this->belongsTo(Commande::class, 'id_commande', 'id_commande'); }
}
