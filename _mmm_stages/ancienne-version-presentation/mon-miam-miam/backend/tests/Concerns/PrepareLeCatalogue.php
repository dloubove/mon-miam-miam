<?php

namespace Tests\Concerns;

use App\Models\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Outils partagés par les tests du catalogue (le DemoSeeder fournit 9 articles, tous au menu du jour).
 */
trait PrepareLeCatalogue
{
    /**
     * Fausse photo pour les tests d'envoi d'images.
     *
     * On n'utilise pas UploadedFile::fake()->image() : il fabrique une vraie image avec l'extension GD,
     * qui n'est pas activée partout (c'est le cas par défaut dans XAMPP). Ici on déclare simplement
     * le type du fichier, ce qui suffit pour tester la validation et l'enregistrement.
     *
     * @param  int  $kilooctets  taille annoncée du fichier
     */
    protected function fausseImage(string $nom = 'photo.jpg', int $kilooctets = 50): UploadedFile
    {
        $type = str_ends_with(strtolower($nom), '.png') ? 'image/png' : 'image/jpeg';

        return UploadedFile::fake()->create($nom, $kilooctets, $type);
    }

    /** Retrouve un article de démo par son nom. */
    protected function article(string $nom): Article
    {
        return Article::where('nom_article', $nom)->firstOrFail();
    }

    /**
     * Noms des articles d'une réponse JSON, dans l'ordre.
     * On vérifie d'abord que la réponse est un succès : sinon une erreur 422 serait confondue avec « aucun résultat ».
     */
    protected function noms($reponse): array
    {
        $reponse->assertOk();

        return collect($reponse->json('data'))->pluck('nom')->all();
    }

    /** Crée un article hors menu du jour et renvoie son id. */
    protected function creerArticle(string $nom, int $prix = 1000, string $categorie = 'plat'): int
    {
        return DB::table('article')->insertGetId([
            'nom_article' => $nom, 'prix_unitaire' => $prix,
            'id_categorie' => DB::table('categorie')->where('libelle_categorie', $categorie)->value('id_categorie'),
            'id_restaurant' => DB::table('restaurant')->value('id_restaurant'),
        ], 'id_article');
    }

    /** Enregistre une commande contenant cet article (pour tester les suppressions interdites). */
    protected function commanderArticle(int $idArticle, ?int $idPromotion = null): int
    {
        $etudiant = DB::table('etudiant')->value('id_utilisateur');
        $commande = DB::table('commande')->insertGetId([
            'mode_service' => 'sur_place', 'heure_arrivee' => '12:00:00', 'id_etudiant' => $etudiant,
            'id_restaurant' => DB::table('restaurant')->value('id_restaurant'), 'id_promotion' => $idPromotion,
        ], 'id_commande');
        DB::table('contenir')->insert(['id_commande' => $commande, 'id_article' => $idArticle, 'quantite' => 1, 'prix_applique' => 1000]);

        return $commande;
    }
}
