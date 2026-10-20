<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Commande d'un étudiant (table `commande`).
 *
 * Cycle de vie :
 *   panier ──confirmer──▶ en_attente ──▶ en_preparation ──▶ prete ──▶ servie (sur place) | livree (livraison)
 *      └──────────────────────┴──────────────────┴──────────────┴──▶ annulee
 * « panier » = commande créée mais pas encore confirmée (en attente de paiement) ; le personnel ne la voit pas.
 */
class Commande extends Model
{
    /** Statuts visibles par le personnel et suivis par l'étudiant comme « en cours ». */
    public const STATUTS_EN_COURS = ['en_attente', 'en_preparation', 'prete'];

    /** Statuts finaux : plus aucune action possible. */
    public const STATUTS_TERMINES = ['servie', 'livree', 'annulee'];

    /** Libellés affichés à l'utilisateur. */
    public const LIBELLES = [
        'panier' => 'En attente de paiement',
        'en_attente' => 'En attente',
        'en_preparation' => 'En préparation',
        'prete' => 'Prête',
        'servie' => 'Servie sur place',
        'livree' => 'Livrée',
        'annulee' => 'Annulée',
    ];

    protected $table = 'commande';

    protected $primaryKey = 'id_commande';

    public $timestamps = false;

    protected $fillable = [
        'date_commande', 'statut_commande', 'mode_service', 'heure_arrivee', 'adresse_livraison',
        'montant_total', 'points_utilises', 'reduction_points', 'points_gagnes',
        'commentaire_client', 'date_commentaire', 'date_validation',
        'id_etudiant', 'id_restaurant', 'id_validateur', 'id_promotion',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'datetime',
            'date_commentaire' => 'datetime',
            'date_validation' => 'datetime',
            // Colonnes DECIMAL / entières : on veut des nombres PHP, pas du texte.
            'montant_total' => 'integer',
            'reduction_points' => 'integer',
            'points_utilises' => 'integer',
            'points_gagnes' => 'integer',
        ];
    }

    /** L'étudiant qui a passé la commande (sa clé est id_utilisateur dans la table etudiant). */
    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'id_etudiant', 'id_utilisateur');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'id_commande', 'id_commande');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'id_commande', 'id_commande');
    }

    /** Journal des changements de statut, du plus ancien au plus récent. */
    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueCommande::class, 'id_commande', 'id_commande')->orderBy('id_historique');
    }

    /** Total des lignes avant réduction (nécessite la relation `lignes` chargée pour éviter une requête). */
    public function sousTotal(): int
    {
        return (int) $this->lignes->sum(fn (LigneCommande $l) => $l->quantite * $l->prix_applique);
    }

    public function libelleStatut(): string
    {
        return self::LIBELLES[$this->statut_commande] ?? $this->statut_commande;
    }
}
