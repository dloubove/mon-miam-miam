<?php

namespace App\Http\Resources;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Categorie */
class CategorieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id_categorie, 'libelle' => $this->libelle_categorie];
    }
}
