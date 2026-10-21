<?php

namespace App\Services;

use App\Models\Article;
use App\Models\MenuJour;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Requêtes et règles du catalogue : liste des articles, menu du jour.
 */
class CatalogueService
{
    public function __construct(private readonly RestaurantService $restaurants) {}

    /**
     * Requête de base : charge ce dont l'affichage a besoin EN UNE FOIS (catégorie + promotions en cours)
     * pour éviter une requête SQL par article.
     */
    private function requeteDeBase(): Builder
    {
        return Article::query()
            ->where('article.id_restaurant', $this->restaurants->id())
            ->with([
                'categorie',
                'promotions' => fn ($q) => $q->actives(),
            ]);
    }

    /** Menu du jour (public) : uniquement les articles proposés aujourd'hui, filtrés et paginés. */
    public function menuDuJour(array $filtres, int $parPage): LengthAwarePaginator
    {
        return $this->requeteDeBase()
            ->auMenuDuJour($this->restaurants->id())
            ->filtrer($filtres)
            ->paginate($parPage);
    }

    /** Catalogue complet (gestion / staff) avec l'indicateur « dans_menu » = présent au menu d'aujourd'hui. */
    public function catalogueComplet(array $filtres, int $parPage): LengthAwarePaginator
    {
        return $this->requeteDeBase()
            ->withExists(['menus as dans_menu' => fn ($q) => $q
                ->where('menu_jour.id_restaurant', $this->restaurants->id())
                ->where('menu_jour.date_menu', now()->toDateString()),
            ])
            ->filtrer($filtres)
            ->paginate($parPage);
    }

    /**
     * Tous les articles du restaurant (sans pagination) avec l'indicateur « dans_menu ».
     * Sert à la page staff « Menu du jour » et à la fenêtre « Composer le menu ».
     */
    public function tousLesArticles()
    {
        return $this->requeteDeBase()
            ->withExists(['menus as dans_menu' => fn ($q) => $q
                ->where('menu_jour.id_restaurant', $this->restaurants->id())
                ->where('menu_jour.date_menu', now()->toDateString()),
            ])
            ->orderBy('id_categorie')
            ->orderBy('nom_article')
            ->get();
    }

    /** Remplace la composition du menu d'aujourd'hui par la liste d'articles donnée. */
    public function composerMenuDuJour(array $idsArticles): MenuJour
    {
        return DB::transaction(function () use ($idsArticles) {
            // firstOrCreate : on réutilise le menu d'aujourd'hui s'il existe, sinon on le crée.
            $menu = MenuJour::firstOrCreate([
                'id_restaurant' => $this->restaurants->id(),
                'date_menu' => now()->toDateString(),
            ]);

            // sync() ajoute les articles manquants et retire ceux qui ne sont plus dans la liste.
            $menu->articles()->sync($idsArticles);

            return $menu;
        });
    }
}
