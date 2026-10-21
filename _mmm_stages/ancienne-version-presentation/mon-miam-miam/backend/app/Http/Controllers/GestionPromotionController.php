<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromotionRequest;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Promotions (gérant et administrateur) : réduction en % sur une sélection d'articles, pendant une période. */
class GestionPromotionController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    /** GET /api/gestion/promotions : toutes les promotions (passées, en cours, à venir). */
    public function index(): AnonymousResourceCollection
    {
        return PromotionResource::collection(
            Promotion::with('articles')->orderByDesc('date_debut_promo')->get(),
        );
    }

    /** POST /api/gestion/promotions */
    public function store(PromotionRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        // Tout ou rien : la promotion et ses liens avec les articles sont créés ensemble.
        $promotion = DB::transaction(function () use ($donnees, $request) {
            $promotion = Promotion::create([
                'titre_promotion' => $donnees['titre'],
                'description_promotion' => $donnees['description'] ?? null,
                'taux_reduction' => $donnees['taux'],
                'date_debut_promo' => $donnees['date_debut'],
                'date_fin_promo' => $donnees['date_fin'],
                'affiche_promo' => $request->hasFile('affiche') ? $this->images->enregistrer($request->file('affiche'), 'promotions') : null,
            ]);
            $promotion->articles()->sync($donnees['articles'] ?? []);

            return $promotion;
        });

        return (new PromotionResource($promotion->load('articles')))->response()->setStatusCode(201);
    }

    /** PATCH /api/gestion/promotions/{promotion} : modifie les champs envoyés. */
    public function update(PromotionRequest $request, Promotion $promotion): PromotionResource
    {
        $donnees = $request->validated();

        $colonnes = [
            'titre' => 'titre_promotion', 'description' => 'description_promotion', 'taux' => 'taux_reduction',
            'date_debut' => 'date_debut_promo', 'date_fin' => 'date_fin_promo',
        ];
        foreach ($colonnes as $champApi => $colonne) {
            if (array_key_exists($champApi, $donnees)) {
                $promotion->{$colonne} = $donnees[$champApi];
            }
        }

        // Si une seule des deux dates est envoyée, la règle « after_or_equal » du formulaire ne suffit pas :
        // on compare avec la date déjà enregistrée.
        if ($promotion->date_fin_promo->lt($promotion->date_debut_promo)) {
            throw ValidationException::withMessages(['date_fin' => ['La date de fin ne peut pas précéder la date de début.']]);
        }

        DB::transaction(function () use ($promotion, $donnees, $request) {
            if ($request->hasFile('affiche')) {
                $this->images->supprimer($promotion->affiche_promo);
                $promotion->affiche_promo = $this->images->enregistrer($request->file('affiche'), 'promotions');
            }
            $promotion->save();

            // Les articles ne changent que si la liste est envoyée.
            if (array_key_exists('articles', $donnees)) {
                $promotion->articles()->sync($donnees['articles']);
            }
        });

        return new PromotionResource($promotion->fresh('articles'));
    }

    /** DELETE /api/gestion/promotions/{promotion} : refusé si une commande l'a utilisée. */
    public function destroy(Promotion $promotion): Response|JsonResponse
    {
        if (DB::table('commande')->where('id_promotion', $promotion->id_promotion)->exists()) {
            return response()->json(['message' => 'Cette promotion a déjà été appliquée à des commandes : elle ne peut pas être supprimée.'], 409);
        }

        $this->images->supprimer($promotion->affiche_promo);
        $promotion->delete();

        return response()->noContent();
    }
}
