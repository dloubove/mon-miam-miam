<?php

namespace App\Services;

use App\Exceptions\RegleMetierException;
use App\Models\Article;
use App\Models\Commande;
use App\Models\MouvementPoints;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier des commandes : calcul des prix, création, confirmation, changements de statut.
 */
class CommandeService
{
    /**
     * Passages de statut autorisés. Tout ce qui n'est pas listé ici est interdit.
     * Une commande « servie », « livrée » ou « annulée » est terminée : aucune sortie.
     */
    private const TRANSITIONS = [
        'panier' => ['en_attente', 'annulee'],
        'en_attente' => ['en_preparation', 'annulee'],
        'en_preparation' => ['prete', 'annulee'],
        'prete' => ['servie', 'livree', 'annulee'],
    ];

    public function __construct(
        private readonly RestaurantService $restaurants,
        private readonly FideliteService $fidelite,
        private readonly PointsService $points,
        private readonly RemboursementService $remboursements,
    ) {}

    // ---------------------------------------------------------------------
    // Calcul du prix (devis) et création
    // ---------------------------------------------------------------------

    /**
     * Calcule ce que coûterait la commande, sans rien enregistrer.
     * Vérifie aussi que chaque article est commandable (au menu d'aujourd'hui et pas en rupture).
     *
     * @param  array<int, array{article_id: int, quantite: int}>  $lignes
     * @return array{lignes: array, sous_total: int, points_utilises: int, reduction_points: int, total: int, points_a_gagner: int}
     *
     * @throws ValidationException si un article n'est pas commandable
     */
    public function chiffrer(Utilisateur $utilisateur, array $lignes, bool $utiliserPoints): array
    {
        $restaurant = $this->restaurants->id();
        $ids = array_column($lignes, 'article_id');

        // Les articles demandés, avec leurs promotions en cours (pour le prix du jour).
        $articles = Article::with(['promotions' => fn ($q) => $q->actives()])
            ->where('id_restaurant', $restaurant)
            ->whereIn('id_article', $ids)
            ->get()->keyBy('id_article');

        // Parmi eux, ceux qui sont au menu d'aujourd'hui.
        $auMenu = Article::query()->auMenuDuJour($restaurant)->whereIn('article.id_article', $ids)
            ->pluck('article.id_article')->all();

        $erreurs = [];
        $detail = [];
        $sousTotal = 0;

        foreach ($lignes as $i => $ligne) {
            $article = $articles->get($ligne['article_id']);

            if (! $article) {
                $erreurs["lignes.$i.article_id"] = ["Cet article n'existe pas."];
            } elseif (! in_array($article->id_article, $auMenu, true)) {
                $erreurs["lignes.$i.article_id"] = ["« {$article->nom_article} » n'est pas au menu d'aujourd'hui."];
            } elseif (! $article->disponible) {
                $erreurs["lignes.$i.article_id"] = ["« {$article->nom_article} » est en rupture de stock."];
            } else {
                $prix = $article->prixActuel(); // prix du jour, promotion comprise
                $detail[] = [
                    'article' => $article, 'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $prix, 'total' => $prix * $ligne['quantite'],
                ];
                $sousTotal += $prix * $ligne['quantite'];
            }
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }

        // Réduction par points (seulement si l'étudiant le demande).
        [$pointsUtilises, $reduction] = $utiliserPoints
            ? $this->fidelite->utilisation($sousTotal, (int) $utilisateur->etudiant->solde_points)
            : [0, 0];

        $total = $sousTotal - $reduction;

        return [
            'lignes' => $detail,
            'sous_total' => $sousTotal,
            'points_utilises' => $pointsUtilises,
            'reduction_points' => $reduction,
            'total' => $total,
            'points_a_gagner' => $this->fidelite->pointsGagnes($total),
        ];
    }

    /**
     * Crée la commande au statut « panier » (en attente de paiement), avec ses lignes au prix figé.
     * Aucun point n'est encore débité : cela se fait à la confirmation.
     *
     * @param  array  $donnees  mode_service, heure_arrivee, adresse_livraison, commentaire, lignes, utiliser_points
     */
    public function creer(Utilisateur $utilisateur, array $donnees): Commande
    {
        $devis = $this->chiffrer($utilisateur, $donnees['lignes'], (bool) ($donnees['utiliser_points'] ?? false));

        return DB::transaction(function () use ($utilisateur, $donnees, $devis) {
            $commande = Commande::create([
                'date_commande' => now(),
                'statut_commande' => 'panier',
                'mode_service' => $donnees['mode_service'],
                // La base exige une heure d'arrivée pour « sur place » et une adresse pour « livraison ».
                'heure_arrivee' => $donnees['mode_service'] === 'sur_place' ? $donnees['heure_arrivee'].':00' : null,
                'adresse_livraison' => $donnees['mode_service'] === 'livraison' ? $donnees['adresse_livraison'] : null,
                // Totaux PROVISOIRES : ils seront recalculés et figés à la confirmation.
                'montant_total' => $devis['total'],
                'points_utilises' => $devis['points_utilises'],
                'reduction_points' => $devis['reduction_points'],
                'points_gagnes' => 0, // crédités au paiement réussi (étape 5)
                'commentaire_client' => $donnees['commentaire'] ?? null,
                'date_commentaire' => ! empty($donnees['commentaire']) ? now() : null,
                'id_etudiant' => $utilisateur->id_utilisateur,
                'id_restaurant' => $this->restaurants->id(),
            ]);

            // Une ligne par article. Le prix enregistré est celui d'AUJOURD'HUI : il ne changera plus.
            foreach ($devis['lignes'] as $ligne) {
                $commande->lignes()->create([
                    'id_article' => $ligne['article']->id_article,
                    'quantite' => $ligne['quantite'],
                    'prix_applique' => $ligne['prix_unitaire'],
                ]);
            }

            $this->noter($commande, 'panier', $utilisateur);

            return $commande;
        });
    }

    // ---------------------------------------------------------------------
    // Confirmation : panier → en_attente (contrôle et débit des points)
    // ---------------------------------------------------------------------

    /**
     * Confirme la commande : elle devient visible du personnel.
     * C'est ici que les totaux sont figés et que les points sont contrôlés puis débités.
     * (À l'étape 5, c'est le paiement réussi qui déclenchera cette confirmation.)
     *
     * @throws RegleMetierException si la commande n'est plus confirmable
     */
    public function confirmer(Commande $commande, Utilisateur $acteur): Commande
    {
        return DB::transaction(function () use ($commande, $acteur) {
            $commande = $this->verrouiller($commande);

            if ($commande->statut_commande !== 'panier') {
                throw new RegleMetierException('Cette commande est déjà confirmée ou terminée.', 409);
            }

            // Une commande trop ancienne a pu changer de prix ou de disponibilité : on refuse.
            $limite = now()->subMinutes((int) config('commandes.validite_panier_minutes'));
            if ($commande->date_commande->lt($limite)) {
                throw new RegleMetierException('Cette commande a expiré : veuillez la refaire.', 409);
            }

            $commande->load('lignes.article');
            $this->verifierDisponibilite($commande);

            $sousTotal = $commande->sousTotal();
            $reduction = 0;

            // Points demandés : on revérifie tout MAINTENANT, car le solde a pu changer depuis la création.
            if ($commande->points_utilises > 0) {
                $reduction = $this->fidelite->reductionPour($commande->points_utilises);
                if ($reduction === null) {
                    throw new RegleMetierException('Le nombre de points doit atteindre le seuil et être un multiple de la tranche.', 422);
                }
                if ($reduction > $sousTotal) {
                    throw new RegleMetierException('La réduction en points dépasse le montant de la commande.', 422);
                }
                // Débit : lève une erreur « solde insuffisant » si les points ne sont plus disponibles.
                $this->points->enregistrer($commande->id_etudiant, 'utilisation', -$commande->points_utilises, $commande->id_commande);
            }

            $commande->update([
                'statut_commande' => 'en_attente',
                'reduction_points' => $reduction,
                'montant_total' => $sousTotal - $reduction,
            ]);
            $this->noter($commande, 'en_attente', $acteur);

            return $commande;
        });
    }

    // ---------------------------------------------------------------------
    // Changements de statut
    // ---------------------------------------------------------------------

    /** « Lancer la préparation » : en_attente → en_preparation, le membre du personnel est noté comme validateur. */
    public function demarrer(Commande $commande, Utilisateur $personnel): Commande
    {
        return $this->transitionner($commande, 'en_preparation', $personnel, [
            'id_validateur' => $personnel->id_utilisateur,
            'date_validation' => now(),
        ]);
    }

    /** « Marquer comme PRÊT » : en_preparation → prete. */
    public function marquerPrete(Commande $commande, Utilisateur $personnel): Commande
    {
        return $this->transitionner($commande, 'prete', $personnel);
    }

    /** « Clôturer » : prete → servie (sur place) ou livree (livraison), selon le mode de service. */
    public function cloturer(Commande $commande, Utilisateur $personnel): Commande
    {
        return $this->transitionner($commande, $commande->mode_service === 'livraison' ? 'livree' : 'servie', $personnel);
    }

    /**
     * Annule la commande. Si des points avaient été débités, ils sont rendus par un mouvement correctif.
     * (Le remboursement d'un paiement sera ajouté à l'étape 5.)
     */
    public function annuler(Commande $commande, Utilisateur $acteur): Commande
    {
        return DB::transaction(function () use ($commande, $acteur) {
            $commande = $this->transitionner($commande, 'annulee', $acteur);

            $this->remboursements->rembourserCommande($commande);

            $debite = MouvementPoints::where('id_commande', $commande->id_commande)->where('type_mouvement', 'utilisation')->exists();
            if ($debite && $commande->points_utilises > 0) {
                // Mouvement correctif positif (le grand livre n'est jamais modifié).
                $this->points->enregistrer($commande->id_etudiant, 'ajustement', $commande->points_utilises, $commande->id_commande);
            }

            return $commande;
        });
    }

    /**
     * Applique un changement de statut en vérifiant qu'il est autorisé.
     *
     * Le verrou (lockForUpdate) empêche deux membres du personnel de traiter la même commande en même temps :
     * le second voit le nouveau statut et reçoit un refus clair au lieu d'écraser le premier.
     *
     * @param  array  $champs  colonnes supplémentaires à mettre à jour avec le statut
     */
    private function transitionner(Commande $commande, string $vers, Utilisateur $acteur, array $champs = []): Commande
    {
        return DB::transaction(function () use ($commande, $vers, $acteur, $champs) {
            $commande = $this->verrouiller($commande);

            if (! in_array($vers, self::TRANSITIONS[$commande->statut_commande] ?? [], true)) {
                throw new RegleMetierException(
                    "Action impossible : la commande est « {$commande->libelleStatut()} ».",
                    409,
                );
            }

            $commande->update(['statut_commande' => $vers] + $champs);
            $this->noter($commande, $vers, $acteur);

            return $commande;
        });
    }

    // ---------------------------------------------------------------------
    // Outils internes
    // ---------------------------------------------------------------------

    /** Relit la commande en la verrouillant (valable jusqu'à la fin de la transaction en cours). */
    private function verrouiller(Commande $commande): Commande
    {
        return Commande::whereKey($commande->id_commande)->lockForUpdate()->firstOrFail();
    }

    /** Écrit une ligne dans le journal des statuts. */
    private function noter(Commande $commande, string $statut, Utilisateur $acteur): void
    {
        $commande->historique()->create([
            'statut_commande' => $statut,
            'date_changement' => now(),
            'id_acteur' => $acteur->id_utilisateur,
        ]);
    }

    /** À la confirmation, chaque article doit être encore au menu et en stock. */
    private function verifierDisponibilite(Commande $commande): void
    {
        $restaurant = $this->restaurants->id();
        $ids = $commande->lignes->pluck('id_article')->all();
        $auMenu = Article::query()->auMenuDuJour($restaurant)->whereIn('article.id_article', $ids)->pluck('article.id_article')->all();

        foreach ($commande->lignes as $ligne) {
            if (! in_array($ligne->id_article, $auMenu, true) || ! $ligne->article->disponible) {
                throw new RegleMetierException("« {$ligne->article->nom_article} » n'est plus disponible.", 409);
            }
        }
    }
}
