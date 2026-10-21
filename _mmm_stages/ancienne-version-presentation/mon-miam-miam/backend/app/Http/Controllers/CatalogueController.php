<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltreArticlesRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategorieResource;
use App\Models\Categorie;
use App\Services\CatalogueService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Partie PUBLIQUE du catalogue : ce que voit un visiteur, connecté ou non.
 */
class CatalogueController extends Controller
{
    public function __construct(private readonly CatalogueService $catalogue) {}

    /** GET /api/categories : toutes les catégories (pour construire les filtres). */
    public function categories(): AnonymousResourceCollection
    {
        return CategorieResource::collection(Categorie::orderBy('id_categorie')->get());
    }

    /**
     * GET /api/menu-du-jour : les articles proposés aujourd'hui, avec filtres, tri et pagination.
     *
     * Page d'accueil : /api/menu-du-jour?type=plats&par_page=3   (3 premiers plats)
     * Page « Voir plus » : /api/menu-du-jour?type=plats&q=poulet&prix_max=3000&tri=prix_asc&page=2
     */
    public function menuDuJour(FiltreArticlesRequest $request): AnonymousResourceCollection
    {
        $filtres = $request->validated();

        // par_page : 12 articles par page par défaut.
        $articles = $this->catalogue->menuDuJour($filtres, (int) ($filtres['par_page'] ?? 12));

        // La réponse contient { data: [...articles], links: {...}, meta: { total, current_page, ... } }.
        return ArticleResource::collection($articles);
    }
}
