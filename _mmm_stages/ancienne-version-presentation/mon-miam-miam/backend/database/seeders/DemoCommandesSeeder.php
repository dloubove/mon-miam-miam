<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Utilisateur;
use App\Services\CommandeService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Commandes de démonstration : de quoi remplir tout de suite le tableau du personnel et l'espace étudiant.
 * Appelé uniquement en environnement « local » (voir DatabaseSeeder). Les commandes sont créées par les
 * VRAIS services de l'application (mêmes règles, mêmes prix figés, même journal des statuts).
 */
class DemoCommandesSeeder extends Seeder
{
    public function run(CommandeService $service): void
    {
        // Déjà fait : on ne duplique pas les commandes.
        if (DB::table('commande')->exists()) {
            return;
        }

        $dan = Utilisateur::where('email', 'etudiant@ucac-icam.com')->firstOrFail();
        $paul = Utilisateur::where('email', 'paul@zeduc.com')->firstOrFail();
        $nathan = Utilisateur::where('email', 'nathan@ucac-icam.com')->firstOrFail();
        $abdul = Utilisateur::where('email', 'abdul@ucac-icam.com')->firstOrFail();

        // --- Historique de Dan (commandes terminées, antidatées) -------------
        $this->terminee($service, $dan, $paul, ['Poulet Braisé' => 2], 'livraison', now()->subDay()->setTime(13, 40));
        $this->terminee($service, $dan, $paul, ['Poisson Braisé' => 1, 'Foléré' => 1], 'sur_place', now()->subDays(6)->setTime(19, 15));

        // --- Le tableau du personnel : une commande par colonne -------------
        // Colonne « Prêt » : Nathan, sur place, il y a 25 minutes.
        $prete = $service->creer($nathan, $this->donnees(['Poisson Braisé' => 2], 'sur_place'));
        $service->confirmer($prete, $nathan);
        $service->demarrer($prete->fresh(), $paul);
        $service->marquerPrete($prete->fresh(), $paul);
        $this->reculer($prete, now()->subMinutes(25));

        // Colonne « En cuisine » : Dan, en livraison, il y a 12 minutes (c'est « sa commande en cours »).
        $enCuisine = $service->creer($dan, $this->donnees(['Poulet Braisé' => 1, 'Cocktail Frais' => 1], 'livraison'));
        $service->confirmer($enCuisine, $dan);
        $service->demarrer($enCuisine->fresh(), $paul);
        $this->reculer($enCuisine, now()->subMinutes(12));

        // Colonne « À préparer » : Abdul (avec un commentaire, il y a 3 minutes) puis Nathan (à l'instant).
        $urgente = $service->creer($abdul, $this->donnees(['Poulet Braisé' => 2, 'Foléré' => 1], 'livraison', 'Beaucoup de piment SVP !'));
        $service->confirmer($urgente, $abdul);
        $this->reculer($urgente, now()->subMinutes(3));

        $recente = $service->creer($nathan, $this->donnees(['Poisson Braisé' => 1], 'sur_place'));
        $service->confirmer($recente, $nathan);
    }

    /** Données d'une commande à partir de « nom d'article => quantité ». */
    private function donnees(array $articles, string $mode, ?string $commentaire = null): array
    {
        return [
            'lignes' => collect($articles)->map(fn ($quantite, $nom) => [
                'article_id' => Article::where('nom_article', $nom)->value('id_article'),
                'quantite' => $quantite,
            ])->values()->all(),
            'mode_service' => $mode,
            'heure_arrivee' => $mode === 'sur_place' ? now()->addMinutes(15)->format('H:i') : null,
            'adresse_livraison' => $mode === 'livraison' ? 'Résidence Les Mangueries' : null,
            'commentaire' => $commentaire,
        ];
    }

    /** Commande menée jusqu'au bout (servie ou livrée), puis antidatée. */
    private function terminee(CommandeService $service, Utilisateur $etudiant, Utilisateur $personnel, array $articles, string $mode, Carbon $date): void
    {
        $commande = $service->creer($etudiant, $this->donnees($articles, $mode));
        $service->confirmer($commande, $etudiant);
        $service->demarrer($commande->fresh(), $personnel);
        $service->marquerPrete($commande->fresh(), $personnel);
        $service->cloturer($commande->fresh(), $personnel);
        $this->reculer($commande, $date);
    }

    /**
     * Déplace la commande dans le passé : sa date de création et celles de son journal.
     * (Les services datent tout à « maintenant » ; ici on simule des commandes plus anciennes.)
     */
    private function reculer(Commande $commande, Carbon $date): void
    {
        $decalage = $date->diffInSeconds($commande->fresh()->date_commande, false);

        DB::table('commande')->where('id_commande', $commande->id_commande)->update(['date_commande' => $date]);
        DB::table('commande')->where('id_commande', $commande->id_commande)->whereNotNull('date_validation')
            ->update(['date_validation' => $date->copy()->addMinutes(2)]);

        // Chaque étape du journal est décalée de la même durée : l'ordre et les écarts sont conservés.
        foreach (DB::table('historique_commande')->where('id_commande', $commande->id_commande)->get() as $ligne) {
            DB::table('historique_commande')->where('id_historique', $ligne->id_historique)
                ->update(['date_changement' => Carbon::parse($ligne->date_changement)->subSeconds($decalage)]);
        }
    }
}
