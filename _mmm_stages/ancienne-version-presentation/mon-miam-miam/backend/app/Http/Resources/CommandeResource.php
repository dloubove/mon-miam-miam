<?php

namespace App\Http\Resources;

use App\Models\Commande;
use App\Services\FideliteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON d'une commande, pour l'étudiant comme pour le personnel.
 * Les blocs « client » et « depuis_minutes » n'apparaissent que si la relation `etudiant` a été chargée
 * (tableau du personnel) ; l'étudiant, lui, connaît déjà son propre nom.
 *
 * @mixin Commande
 */
class CommandeResource extends JsonResource
{
    /** Étapes affichées dans le suivi, dans l'ordre. */
    private const ETAPES = [
        'en_attente' => 'Commande validée',
        'en_preparation' => 'En préparation',
        'prete' => 'Prête',
    ];

    public function toArray(Request $request): array
    {
        $lignes = $this->lignes;
        $sousTotal = $this->sousTotal();

        return [
            'id' => $this->id_commande,
            'statut' => $this->statut_commande,
            'statut_libelle' => $this->libelleStatut(),
            'mode_service' => $this->mode_service,
            // La base renvoie 12:30:00 ; l'affichage n'a besoin que de 12:30.
            'heure_arrivee' => $this->heure_arrivee ? substr($this->heure_arrivee, 0, 5) : null,
            'adresse_livraison' => $this->adresse_livraison,
            'commentaire' => $this->commentaire_client,
            'date_commande' => $this->date_commande->toIso8601String(),

            'lignes' => $lignes->map(fn ($l) => [
                'article_id' => $l->id_article,
                'nom' => $l->article?->nom_article,
                'quantite' => $l->quantite,
                'prix_unitaire' => $l->prix_applique, // prix figé au moment de la commande
                'total' => $l->quantite * $l->prix_applique,
            ])->values(),

            'sous_total' => $sousTotal,
            'points_utilises' => $this->points_utilises,
            'reduction_points' => $this->reduction_points,
            // Pour une commande pas encore confirmée, le total est provisoire (recalculé à la confirmation).
            'total' => $this->montant_total,
            'points_gagnes' => $this->points_gagnes,
            'paiement' => $this->whenLoaded('paiements', function () {
                $paiement = $this->paiements->sortByDesc('id_paiement')->first();
                return $paiement?->only(['mode_paiement', 'statut_paiement']);
            }),
            'points_a_gagner' => in_array($this->statut_commande, ['panier', 'en_attente'], true)
                ? app(FideliteService::class)->pointsGagnes($this->montant_total, $this->tauxGain($request)) : null,

            // Frise de suivi : chaque étape avec sa date si elle est atteinte.
            'etapes' => $this->whenLoaded('historique', fn () => $this->etapes()),

            // Réservé au tableau du personnel.
            'client' => $this->when($this->relationLoaded('etudiant'), fn () => [
                'nom' => $this->etudiant->utilisateur?->nom,
                'telephone' => $this->etudiant->utilisateur?->telephone,
            ]),
            'depuis_minutes' => $this->when(
                $this->relationLoaded('etudiant'),
                fn () => max(0, (int) floor($this->date_commande->diffInMinutes(now(), true))),
            ),
        ];
    }

    /**
     * Taux de gain de points, lu UNE SEULE FOIS par requête HTTP (mémorisé dans la requête).
     * Sans cela, une liste de 50 commandes relirait le paramètre en base 50 fois (problème « N+1 »).
     */
    private function tauxGain(Request $request): int
    {
        return $request->attributes->get('taux_gain_points')
            ?? tap(app(FideliteService::class)->tauxGain(), fn ($taux) => $request->attributes->set('taux_gain_points', $taux));
    }

    /** Construit la frise : [{statut, libelle, atteinte, date}, …] à partir du journal des statuts. */
    private function etapes(): array
    {
        // Date de la première apparition de chaque statut.
        $dates = $this->historique->groupBy('statut_commande')->map(fn ($l) => $l->first()->date_changement);

        // La dernière étape dépend du mode : « Livrée » ou « Servie sur place ».
        $etapes = self::ETAPES + [
            $this->mode_service === 'livraison' ? 'livree' : 'servie' => Commande::LIBELLES[$this->mode_service === 'livraison' ? 'livree' : 'servie'],
        ];

        $resultat = [];
        foreach ($etapes as $statut => $libelle) {
            $date = $dates->get($statut);
            $resultat[] = ['statut' => $statut, 'libelle' => $libelle, 'atteinte' => $date !== null, 'date' => $date?->toIso8601String()];
        }

        return $resultat;
    }
}
