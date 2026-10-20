<?php

namespace App\Services;

use App\Contracts\PaiementGateway;
use App\Exceptions\RegleMetierException;
use App\Models\Commande;
use App\Models\Etudiant;
use App\Models\Paiement;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaiementService
{
    public function __construct(
        private readonly PaiementGateway $gateway,
        private readonly CommandeService $commandes,
        private readonly PointsService $points,
        private readonly ParametreService $parametres,
    ) {}

    public function initier(Commande $commande, Utilisateur $etudiant, string $telephone, string $operateur): Paiement
    {
        $paiement = DB::transaction(function () use ($commande, $etudiant, $operateur) {
            $commande = Commande::whereKey($commande->id_commande)->lockForUpdate()->firstOrFail();
            if ($commande->id_etudiant !== $etudiant->id_utilisateur || $commande->statut_commande !== 'panier') {
                throw new RegleMetierException('Cette commande ne peut pas être payée.', 409);
            }
            if ($commande->montant_total <= 0) {
                $this->commandes->confirmer($commande, $etudiant);
                return new Paiement(['montant_paye' => 0, 'mode_paiement' => 'points', 'statut_paiement' => 'reussi', 'fournisseur' => 'points']);
            }
            $enAttente = Paiement::where('id_commande', $commande->id_commande)->where('statut_paiement', 'en_attente')->first();
            if ($enAttente) {
                if ($enAttente->mode_paiement !== 'mobile_money') {
                    throw new RegleMetierException('Cette commande a déjà un autre mode de paiement en attente.', 409);
                }
                return $enAttente;
            }

            $idempotence = (string) Str::uuid();
            $paiement = Paiement::create([
                'montant_paye' => $commande->montant_total,
                'mode_paiement' => 'mobile_money',
                'fournisseur' => config('services.mobile_money.mode') === 'cinetpay' ? 'cinetpay' : $operateur,
                'cle_idempotence' => $idempotence,
                'statut_paiement' => 'en_attente',
                'date_paiement' => now(),
                'id_commande' => $commande->id_commande,
            ]);
            return $paiement;
        });

        // L'appel réseau reste hors transaction SQL : la clé d'idempotence est déjà conservée
        // si l'agrégateur répond lentement ou si la connexion est interrompue.
        if (! $paiement->exists || $paiement->reference_transaction || $paiement->url_paiement) return $paiement;
        $resultat = $this->gateway->initier($paiement, $telephone, $operateur);
        $paiement->update([
            'reference_transaction' => $resultat['reference'],
            'url_paiement' => $resultat['url'],
            'reponse_fournisseur' => $resultat['raw'] ?? ['message' => $resultat['message']],
        ]);
        return $paiement->fresh();
    }

    /** Enregistre une commande payable en espèces ; elle entre en cuisine sans encaisser d'argent. */
    public function initierEspeces(Commande $commande, Utilisateur $etudiant): Paiement
    {
        return DB::transaction(function () use ($commande, $etudiant) {
            $commande = Commande::whereKey($commande->id_commande)->lockForUpdate()->firstOrFail();
            if ($commande->id_etudiant !== $etudiant->id_utilisateur || $commande->statut_commande !== 'panier') {
                throw new RegleMetierException('Cette commande ne peut pas être payée.', 409);
            }
            if ($commande->montant_total <= 0) {
                $this->commandes->confirmer($commande, $etudiant);
                return new Paiement(['montant_paye' => 0, 'mode_paiement' => 'points', 'statut_paiement' => 'reussi', 'fournisseur' => 'points']);
            }
            if (Paiement::where('id_commande', $commande->id_commande)->where('statut_paiement', 'en_attente')->exists()) {
                throw new RegleMetierException('Cette commande a déjà un paiement en attente.', 409);
            }

            $paiement = Paiement::create([
                'montant_paye' => $commande->montant_total,
                'mode_paiement' => 'especes',
                'fournisseur' => 'especes',
                'cle_idempotence' => (string) Str::uuid(),
                'statut_paiement' => 'en_attente',
                'date_paiement' => now(),
                'id_commande' => $commande->id_commande,
            ]);
            $this->commandes->confirmer($commande, $etudiant);
            return $paiement->fresh();
        });
    }

    /** Encaissement physique confirmé par un membre du personnel autorisé. */
    public function encaisserEspeces(Commande $commande): Paiement
    {
        return DB::transaction(function () use ($commande) {
            $commande = Commande::whereKey($commande->id_commande)->lockForUpdate()->firstOrFail();
            $paiement = Paiement::where('id_commande', $commande->id_commande)
                ->where('mode_paiement', 'especes')->lockForUpdate()->firstOrFail();
            if ($paiement->statut_paiement !== 'en_attente') {
                throw new RegleMetierException('Ce paiement en espèces a déjà été traité.', 409);
            }
            if (in_array($commande->statut_commande, ['annulee', 'panier'], true)) {
                throw new RegleMetierException('Cette commande ne peut pas être encaissée.', 409);
            }

            $paiement->update(['statut_paiement' => 'reussi', 'date_paiement' => now()]);
            $this->attribuerPointsEtParrainage($paiement, $commande);
            return $paiement->fresh();
        });
    }

    /** Une notification signée ne crédite les points qu'une fois, dans une même transaction. */
    public function traiterNotification(array $donnees): Paiement
    {
        return DB::transaction(function () use ($donnees) {
            $paiement = Paiement::where(fn ($q) => $q->where('cle_idempotence', $donnees['reference'])
                ->orWhere('reference_transaction', $donnees['reference']))->lockForUpdate()->firstOrFail();
            if ($paiement->statut_paiement === 'reussi') {
                return $paiement;
            }
            if ($donnees['status'] === 'pending') {
                // Certains opérateurs attendent encore la validation sur le téléphone : le paiement reste ouvert.
                return $paiement;
            }
            if ($paiement->statut_paiement !== 'en_attente') {
                throw new RegleMetierException('Ce paiement n’est plus en attente.', 409);
            }

            if ($donnees['status'] !== 'success') {
                $paiement->update(['statut_paiement' => 'echoue']);
                return $paiement->fresh();
            }

            $commande = Commande::whereKey($paiement->id_commande)->lockForUpdate()->firstOrFail();
            if ((int) $donnees['amount'] !== $paiement->montant_paye) {
                throw new RegleMetierException('Le montant confirmé par le fournisseur est incorrect.', 422);
            }

            if ($commande->statut_commande === 'panier') {
                $this->commandes->confirmer($commande, $commande->etudiant->utilisateur);
            } elseif ($commande->statut_commande !== 'en_attente') {
                throw new RegleMetierException('La commande ne peut plus être confirmée par ce paiement.', 409);
            }
            $paiement->update([
                'statut_paiement' => 'reussi',
                'reference_transaction' => $donnees['provider_reference'] ?? $paiement->reference_transaction,
                'date_paiement' => now(),
            ]);

            $this->attribuerPointsEtParrainage($paiement, $commande);

            return $paiement->fresh();
        });
    }

    private function attribuerPointsEtParrainage(Paiement $paiement, Commande $commande): void
    {
        $points = intdiv($paiement->montant_paye, 1000) * $this->parametres->entierRequis('points_par_1000_fcfa');
        $commande->update(['points_gagnes' => $points]);
        if ($points > 0) {
            $mois = $this->parametres->entier('duree_validite_points_mois', 12);
            $this->points->enregistrer($commande->id_etudiant, 'gain_commande', $points, $commande->id_commande, now()->addMonths($mois)->toDateString());
        }
        $this->recompenserParrainSiPremiereCommandePayee($commande);
    }

    private function recompenserParrainSiPremiereCommandePayee(Commande $commande): void
    {
        $filleul = Etudiant::whereKey($commande->id_etudiant)->lockForUpdate()->first();
        if (! $filleul || ! $filleul->id_parrain || $filleul->recompense_attribuee) {
            return;
        }
        $aUneCommandePayeeAvant = Paiement::where('statut_paiement', 'reussi')
            ->whereHas('commande', fn ($q) => $q->where('id_etudiant', $filleul->id_utilisateur)->where('id_commande', '<>', $commande->id_commande))
            ->exists();
        if ($aUneCommandePayeeAvant) {
            return;
        }

        $gain = (int) ($filleul->points_parrainage ?: $this->parametres->entier('points_parrainage', 10));
        if ($gain > 0) {
            $this->points->enregistrer($filleul->id_parrain, 'gain_parrainage', $gain);
        }
        $filleul->update(['recompense_attribuee' => true]);
    }
}
