<?php

namespace App\Http\Resources;

use App\Models\Promotion;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Promotion */
class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $aujourdhui = now()->toDateString();
        $debut = $this->date_debut_promo->toDateString();
        $fin = $this->date_fin_promo->toDateString();

        return [
            'id' => $this->id_promotion,
            'titre' => $this->titre_promotion,
            'description' => $this->description_promotion,
            'taux' => $this->taux_reduction,
            'date_debut' => $debut,
            'date_fin' => $fin,
            // Calculé à chaque lecture : a_venir, en_cours ou terminee.
            'statut' => $aujourdhui < $debut ? 'a_venir' : ($aujourdhui > $fin ? 'terminee' : 'en_cours'),
            'affiche_url' => app(ImageService::class)->url($this->affiche_promo),
            'articles' => $this->whenLoaded('articles', fn () => $this->articles->map(fn ($a) => [
                'id' => $a->id_article, 'nom' => $a->nom_article,
            ])),
        ];
    }
}
