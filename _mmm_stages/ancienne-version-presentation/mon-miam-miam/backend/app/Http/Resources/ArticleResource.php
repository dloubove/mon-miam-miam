<?php

namespace App\Http\Resources;

use App\Models\Article;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON d'un article pour le frontend.
 *
 * Le prix est déjà « final » : si une promotion est en cours, `prix` est le prix remisé et
 * `prix_normal` garde l'ancien prix (pour l'afficher barré). Sans promotion, `prix_normal` vaut null.
 *
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $promotion = $this->promotionActive();

        return [
            'id' => $this->id_article,
            'nom' => $this->nom_article,
            'description' => $this->description_article,
            'categorie' => new CategorieResource($this->whenLoaded('categorie')),
            'prix' => $this->prixActuel(),
            'prix_normal' => $promotion ? $this->prix_unitaire : null,
            'promotion' => $promotion ? [
                'id' => $promotion->id_promotion,
                'titre' => $promotion->titre_promotion,
                'taux' => $promotion->taux_reduction,
            ] : null,
            'image_url' => app(ImageService::class)->url($this->image_article),
            // false = rupture de stock : affiché mais non commandable.
            'disponible' => $this->disponible,
            // Présent uniquement dans les listes du staff / de la direction.
            'dans_menu' => $this->when(
                array_key_exists('dans_menu', $this->resource->getAttributes()),
                fn () => (bool) $this->resource->getAttributes()['dans_menu'],
            ),
        ];
    }
}
