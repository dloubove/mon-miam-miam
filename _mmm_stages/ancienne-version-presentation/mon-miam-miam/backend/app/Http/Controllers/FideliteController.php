<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Consultations du solde, du grand livre de points et des filleuls d'un étudiant connecté. */
class FideliteController extends Controller
{
    public function historique(Request $request)
    {
        return DB::table('mouvement_points')->where('id_etudiant', $request->user()->id_utilisateur)
            ->orderByDesc('date_mouvement')->orderByDesc('id_mouvement')->paginate(30);
    }

    public function parrainages(Request $request)
    {
        $parrain = $request->user()->id_utilisateur;
        return DB::table('etudiant as f')->join('utilisateur as u', 'u.id_utilisateur', '=', 'f.id_utilisateur')
            ->where('f.id_parrain', $parrain)->orderByDesc('f.date_parrainage')
            ->select('f.id_utilisateur as id', 'u.nom as nom', 'f.date_parrainage', 'f.recompense_attribuee', 'f.points_parrainage')
            ->get()->map(function ($filleul) {
                $premiere = DB::table('commande as c')->join('paiement as p', 'p.id_commande', '=', 'c.id_commande')
                    ->where('c.id_etudiant', $filleul->id)->where('p.statut_paiement', 'reussi')
                    ->orderBy('p.date_paiement')->select('c.id_commande', 'p.date_paiement')->first();
                return [
                    'id' => $filleul->id, 'nom' => $filleul->nom,
                    'date_parrainage' => $filleul->date_parrainage,
                    'premiere_commande_payee' => (bool) $premiere,
                    'premiere_commande_id' => $premiere?->id_commande,
                    'recompense_attribuee' => (bool) $filleul->recompense_attribuee,
                    'points_recompense' => $filleul->recompense_attribuee ? $filleul->points_parrainage : null,
                ];
            });
    }
}
