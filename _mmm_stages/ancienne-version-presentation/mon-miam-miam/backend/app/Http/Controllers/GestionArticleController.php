<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Http\Requests\FiltreArticlesRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\CatalogueService;
use App\Services\ImageService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * « Le Grand Catalogue » : création, modification et suppression des articles
 * (gérant et administrateur ; la suppression est réservée à l'administrateur, voir routes/api.php).
 */
class GestionArticleController extends Controller
{
    public function __construct(
        private readonly CatalogueService $catalogue,
        private readonly ImageService $images,
        private readonly RestaurantService $restaurants,
    ) {}

    /** GET /api/gestion/articles : tout le catalogue (même les articles absents du menu du jour). */
    public function index(FiltreArticlesRequest $request): AnonymousResourceCollection
    {
        $filtres = $request->validated();

        return ArticleResource::collection($this->catalogue->catalogueComplet($filtres, (int) ($filtres['par_page'] ?? 20)));
    }

    /** POST /api/gestion/articles : ajoute un article au catalogue. */
    public function store(ArticleRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        $article = Article::create([
            'nom_article' => $donnees['nom'],
            'description_article' => $donnees['description'] ?? null,
            'prix_unitaire' => $donnees['prix'],
            'id_categorie' => $donnees['categorie_id'],
            'disponible' => $donnees['disponible'] ?? true,
            'id_restaurant' => $this->restaurants->id(),
            // Le fichier n'est enregistré que s'il y en a un.
            'image_article' => $request->hasFile('image') ? $this->images->enregistrer($request->file('image'), 'articles') : null,
        ]);

        return (new ArticleResource($this->recharger($article)))->response()->setStatusCode(201);
    }

    /**
     * PATCH /api/gestion/articles/{article} : modifie les champs envoyés (les autres restent inchangés).
     * Avec un formulaire contenant une photo, le navigateur doit envoyer POST + champ « _method=PATCH ».
     */
    public function update(ArticleRequest $request, Article $article): ArticleResource
    {
        $donnees = $request->validated();

        // Correspondance entre les noms de l'API (nom, prix…) et les colonnes de la base (nom_article, prix_unitaire…).
        $colonnes = [
            'nom' => 'nom_article', 'description' => 'description_article', 'prix' => 'prix_unitaire',
            'categorie_id' => 'id_categorie', 'disponible' => 'disponible',
        ];
        foreach ($colonnes as $champApi => $colonne) {
            if (array_key_exists($champApi, $donnees)) {
                $article->{$colonne} = $donnees[$champApi];
            }
        }

        if ($request->hasFile('image')) {
            // Nouvelle photo : on supprime l'ancienne pour ne pas laisser de fichiers orphelins.
            $this->images->supprimer($article->image_article);
            $article->image_article = $this->images->enregistrer($request->file('image'), 'articles');
        } elseif ($request->boolean('supprimer_image')) {
            $this->images->supprimer($article->image_article);
            $article->image_article = null;
        }

        $article->save();

        return new ArticleResource($this->recharger($article));
    }

    /**
     * DELETE /api/gestion/articles/{article} : supprime un article (administrateur uniquement).
     * Refusé s'il a déjà été commandé : l'historique des commandes doit rester cohérent.
     */
    public function destroy(Article $article): Response|JsonResponse
    {
        if ($article->aDejaEteCommande()) {
            return response()->json([
                'message' => 'Cet article a déjà été commandé : il ne peut pas être supprimé. Marquez-le plutôt en rupture et retirez-le du menu.',
            ], 409);
        }

        $this->images->supprimer($article->image_article);
        // Les liens avec les menus et les promotions disparaissent automatiquement (suppression en cascade).
        $article->delete();

        return response()->noContent();
    }

    /** Recharge l'article avec ses relations pour que la réponse soit complète et à jour. */
    private function recharger(Article $article): Article
    {
        return $article->fresh(['categorie', 'promotions' => fn ($q) => $q->actives()]);
    }
}
