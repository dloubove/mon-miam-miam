<?php

namespace App\Services;

use App\Exceptions\RegleMetierException;
use App\Models\Etudiant;
use App\Models\MouvementPoints;
use Illuminate\Support\Facades\DB;

/**
 * Seul point d'entrée pour faire bouger les points d'un étudiant.
 *
 * Règle d'origine : le « grand livre » (table mouvement_points) est la source de vérité,
 * et etudiant.solde_points n'en est que le total. On ne modifie JAMAIS le solde directement :
 * on enregistre un mouvement, et ce service met le solde à jour dans la même transaction.
 */
class PointsService
{
    /**
     * Enregistre un mouvement (positif = gain, négatif = dépense) et met à jour le solde.
     *
     * @param  string  $type  gain_commande, gain_parrainage, gain_evenement, gain_mini_jeu, utilisation, expiration, ajustement
     * @param  int  $points  nombre de points, signé (les contraintes de la base vérifient le signe selon le type)
     *
     * @throws RegleMetierException si le solde deviendrait négatif
     */
    public function enregistrer(int $idEtudiant, string $type, int $points, ?int $idCommande = null, ?string $expiration = null): MouvementPoints
    {
        return DB::transaction(function () use ($idEtudiant, $type, $points, $idCommande, $expiration) {
            // lockForUpdate : on verrouille la ligne de l'étudiant jusqu'à la fin de la transaction.
            // Sans cela, deux commandes simultanées pourraient lire le même solde et dépenser deux fois les mêmes points.
            $etudiant = Etudiant::whereKey($idEtudiant)->lockForUpdate()->first();
            if (! $etudiant) {
                throw new RegleMetierException('Étudiant introuvable.', 404);
            }

            $nouveauSolde = $etudiant->solde_points + $points;
            if ($nouveauSolde < 0) {
                throw new RegleMetierException(
                    "Solde de points insuffisant : {$etudiant->solde_points} disponible(s), ".abs($points).' demandé(s).',
                    422,
                );
            }

            $mouvement = MouvementPoints::create([
                'type_mouvement' => $type,
                'nombre_points' => $points,
                'date_mouvement' => now(),
                'date_expiration_points' => $expiration,
                'id_etudiant' => $idEtudiant,
                'id_commande' => $idCommande,
            ]);

            $etudiant->update(['solde_points' => $nouveauSolde]);

            return $mouvement;
        });
    }
}
