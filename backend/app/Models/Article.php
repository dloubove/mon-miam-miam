<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * Article vendu (table `article`) : plat, snack, boisson ou dessert.
 *
 * Deux notions à ne pas confondre :
 *  - `disponible`  : false = « rupture de stock » (l'article reste affiché mais ne peut pas être commandé) ;
 *  - être dans le menu du jour : table `composer` (ce que le restaurant propose aujourd'hui).
 */
class Article extends Model
{
    protected $table = 'article';

    protected $primaryKey = 'id_article';

    public $timestamps = false;

    protected $fillable = [
        'nom_article', 'description_article', 'prix_unitaire', 'image_article',
        'disponible', 'id_categorie', 'id_restaurant',
    ];

    protected function casts(): array
    {
        return [
            // Colonne DECIMAL(10,0) : sans ce cast, PHP recevrait le texte "2500" au lieu du nombre 2500.
            'prix_unitaire' => 'integer',
            'disponible' => 'boolean',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'id_categorie', 'id_categorie');
    }

    /** Promotions liées à l'article (en cours ou non), via `concerner`. */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'concerner', 'id_article', 'id_promotion');
    }

    /** Menus du jour dans lesquels l'article apparaît, via `composer`. */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(MenuJour::class, 'composer', 'id_article', 'id_menu_jour');
    }

    /**
     * Promotion en cours la plus avantageuse (null s'il n'y en a pas).
     * Attention : charger la relation `promotions` AVANT (with(['promotions' => fn ($q) => $q->actives()])),
     * sinon une requête SQL est exécutée pour chaque article (lenteur).
     */
    public function promotionActive(): ?Promotion
    {
        $promotions = $this->relationLoaded('promotions') ? $this->promotions : $this->promotions()->actives()->get();

        return $promotions->filter(fn (Promotion $p) => $p->estActive())->sortByDesc('taux_reduction')->first();
    }

    /** Prix à payer aujourd'hui : prix normal, ou prix remisé (arrondi au FCFA) s'il y a une promotion. */
    public function prixActuel(): int
    {
        $promotion = $this->promotionActive();

        return $promotion
            ? (int) round($this->prix_unitaire * (1 - $promotion->taux_reduction / 100))
            : $this->prix_unitaire;
    }

    /** Vrai si l'article a déjà été commandé (on ne peut alors plus le supprimer). */
    public function aDejaEteCommande(): bool
    {
        return DB::table('contenir')->where('id_article', $this->id_article)->exists();
    }

    // ---------------------------------------------------------------------
    // Filtres de recherche (catalogue public et gestion)
    // ---------------------------------------------------------------------

    /**
     * Expression SQL du prix actuel : même calcul que prixActuel(), mais exécuté par la base,
     * pour pouvoir FILTRER et TRIER sur le prix réellement payé (un article à 1250 FCFA
     * remisé à 500 doit apparaître dans « moins de 600 FCFA »).
     * Contient UN paramètre « ? » : la date du jour.
     */
    public static function sqlPrixActuel(): string
    {
        return '(ROUND(article.prix_unitaire * (1 - COALESCE((
                    SELECT MAX(p.taux_reduction)
                    FROM promotion p
                    JOIN concerner c ON c.id_promotion = p.id_promotion
                    WHERE c.id_article = article.id_article
                      AND CAST(? AS date) BETWEEN p.date_debut_promo AND p.date_fin_promo
                ), 0) / 100)))';
    }

    /** Limite aux articles du menu du jour du restaurant. */
    public function scopeAuMenuDuJour(Builder $requete, int $restaurantId): Builder
    {
        return $requete->whereIn('article.id_article', function ($sous) use ($restaurantId) {
            $sous->select('composer.id_article')
                ->from('composer')
                ->join('menu_jour', 'menu_jour.id_menu_jour', '=', 'composer.id_menu_jour')
                ->where('menu_jour.id_restaurant', $restaurantId)
                ->where('menu_jour.date_menu', now()->toDateString());
        });
    }

    /**
     * Applique les filtres du formulaire « Voir plus » :
     * type, categorie, q, prix_min, prix_max, disponible, promo, tri.
     */
    public function scopeFiltrer(Builder $requete, array $f): Builder
    {
        $aujourdhui = now()->toDateString();

        // Onglet « Plats » (tout sauf les boissons) ou « Boissons ».
        if (($f['type'] ?? null) === 'plats') {
            $requete->whereHas('categorie', fn ($q) => $q->where('libelle_categorie', '!=', 'boisson'));
        } elseif (($f['type'] ?? null) === 'boissons') {
            $requete->whereHas('categorie', fn ($q) => $q->where('libelle_categorie', 'boisson'));
        }

        // Catégorie précise, par identifiant (« 2 ») ou par nom (« snack »).
        if (! empty($f['categorie'])) {
            $requete->whereHas('categorie', fn ($q) => ctype_digit((string) $f['categorie'])
                ? $q->where('id_categorie', (int) $f['categorie'])
                : $q->where('libelle_categorie', mb_strtolower($f['categorie'])));
        }

        // Recherche texte (insensible à la casse, dans le nom et la description).
        if (! empty($f['q'])) {
            // On neutralise % _ \ saisis par l'utilisateur : ils n'ont pas de sens spécial pour lui.
            $motif = '%'.addcslashes(trim($f['q']), '%_\\').'%';
            $requete->where(fn ($q) => $q->where('nom_article', 'ilike', $motif)->orWhere('description_article', 'ilike', $motif));
        }

        // Fourchette de prix, sur le prix réellement payé (promotion comprise).
        if (isset($f['prix_min'])) {
            $requete->whereRaw(self::sqlPrixActuel().' >= ?', [$aujourdhui, (int) $f['prix_min']]);
        }
        if (isset($f['prix_max'])) {
            $requete->whereRaw(self::sqlPrixActuel().' <= ?', [$aujourdhui, (int) $f['prix_max']]);
        }

        if (! empty($f['disponible'])) {
            $requete->where('article.disponible', true);
        }

        // « En promo uniquement » : au moins une promotion en cours liée à l'article.
        if (! empty($f['promo'])) {
            $requete->whereHas('promotions', fn ($q) => $q->actives());
        }

        // Tri. Ex æquo départagés par l'identifiant pour un ordre stable d'une page à l'autre.
        match ($f['tri'] ?? 'defaut') {
            'prix_asc' => $requete->orderByRaw(self::sqlPrixActuel().' ASC', [$aujourdhui]),
            'prix_desc' => $requete->orderByRaw(self::sqlPrixActuel().' DESC', [$aujourdhui]),
            'nom' => $requete->orderBy('nom_article'),
            default => null,
        };

        return $requete->orderBy('article.id_article');
    }
}
