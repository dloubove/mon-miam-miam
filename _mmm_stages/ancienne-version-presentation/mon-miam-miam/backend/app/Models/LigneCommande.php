<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Ligne d'une commande (table `contenir`) : un article, une quantité, un prix FIGÉ.
 *
 * `prix_applique` est le prix payé au moment de la commande (promotion comprise) :
 * il ne change plus, même si l'article change de prix ensuite.
 * La clé primaire de la table est composée (commande + article) ; Eloquent ne gère pas les clés
 * composées pour modifier une ligne, mais l'insertion et la lecture, seules utilisées ici, fonctionnent.
 */
class LigneCommande extends Model
{
    protected $table = 'contenir';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_commande', 'id_article', 'quantite', 'prix_applique'];

    protected function casts(): array
    {
        return ['quantite' => 'integer', 'prix_applique' => 'integer'];
    }

    /**
     * Règle d'origine : les lignes d'une commande confirmée sont IMMUABLES (prix figés, contenu définitif).
     * Seule une commande encore au statut « panier » peut voir ses lignes modifiées ou supprimées.
     * (La suppression d'une commande entière reste possible : elle emporte ses lignes en cascade.)
     */
    protected static function booted(): void
    {
        $verifier = function (LigneCommande $ligne) {
            $statut = Commande::whereKey($ligne->id_commande)->value('statut_commande');

            if ($statut !== null && $statut !== 'panier') {
                throw new LogicException('Les lignes d\'une commande confirmée sont immuables.');
            }
        };

        static::updating($verifier);
        static::deleting($verifier);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'id_article', 'id_article');
    }
}
