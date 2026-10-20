<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuDuJourRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\CatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion du service au quotidien (employé, gérant, administrateur) :
 * marquer un plat en rupture, composer le menu du jour.
 */
class StaffMenuController extends Controller
{
    public function __construct(private readonly CatalogueService $catalogue) {}

    /** GET /api/staff/menu : tous les articles, avec « dans_menu » (présent au menu d'aujourd'hui) et « disponible ». */
    public function show(): JsonResponse
    {
        return response()->json([
            'date' => now()->toDateString(),
            'articles' => ArticleResource::collection($this->catalogue->tousLesArticles()),
        ]);
    }

    /** PUT /api/staff/menu-du-jour : remplace la composition du menu d'aujourd'hui. */
    public function composer(MenuDuJourRequest $request): JsonResponse
    {
        $this->catalogue->composerMenuDuJour($request->validated()['articles']);

        // On renvoie le nouvel état pour que le frontend se mette à jour sans seconde requête.
        return $this->show();
    }

    /** PATCH /api/staff/articles/{article}/disponibilite : bascule rupture / disponible en un clic. */
    public function disponibilite(Request $request, Article $article): ArticleResource
    {
        $donnees = $request->validate(['disponible' => ['required', 'boolean']]);

        $article->update(['disponible' => $donnees['disponible']]);

        return new ArticleResource($article->load(['categorie', 'promotions' => fn ($q) => $q->actives()]));
    }
}
