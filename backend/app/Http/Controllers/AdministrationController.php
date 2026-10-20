<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministrationController extends Controller
{
    public function tableauDeBord()
    {
        return response()->json([
            'utilisateurs' => DB::table('utilisateur')->where('statut_compte', 'actif')->count(),
            'etudiants' => DB::table('etudiant')->count(),
            'commandes_en_cours' => DB::table('commande')->whereIn('statut_commande', ['en_attente', 'en_preparation', 'prete'])->count(),
            'commandes_payees' => DB::table('paiement')->where('statut_paiement', 'reussi')->count(),
            'chiffre_affaires_fcfa' => (int) DB::table('paiement')->where('statut_paiement', 'reussi')->sum('montant_paye'),
            'reclamations_ouvertes' => DB::table('reclamation')->whereIn('statut_reclamation', ['ouverte', 'repondue'])->count(),
        ]);
    }

    public function equipe()
    {
        return DB::table('personnel as p')->join('utilisateur as u', 'u.id_utilisateur', '=', 'p.id_utilisateur')
            ->join('role as r', 'r.id_role', '=', 'p.id_role')
            ->select('u.id_utilisateur', 'u.nom', 'u.email', 'u.telephone', 'u.statut_compte', 'r.libelle_role')
            ->orderBy('r.libelle_role')->orderBy('u.nom')->get();
    }

    /** Étudiants ayant réellement passé une commande dans le restaurant demandé. */
    public function etudiantsRestaurant(int $restaurant)
    {
        return DB::table('commande as c')->join('etudiant as e', 'e.id_utilisateur', '=', 'c.id_etudiant')
            ->join('utilisateur as u', 'u.id_utilisateur', '=', 'e.id_utilisateur')
            ->where('c.id_restaurant', $restaurant)->distinct()
            ->select('u.id_utilisateur', 'u.nom', 'u.email', 'e.solde_points')->orderBy('u.nom')->paginate(30);
    }

    public function parametres()
    {
        return DB::table('parametre')->orderBy('cle_parametre')->get(['id_parametre', 'cle_parametre', 'valeur_parametre', 'description_parametre']);
    }

    public function modifierParametres(Request $request)
    {
        $data = $request->validate(['parametres' => ['required', 'array', 'min:1'], 'parametres.*' => ['required', 'integer', 'min:0', 'max:1000000']]);
        DB::transaction(function () use ($data) {
            foreach ($data['parametres'] as $cle => $valeur) {
                DB::table('parametre')->where('cle_parametre', $cle)->update(['valeur_parametre' => (string) $valeur]);
            }
        });
        return $this->parametres();
    }
}
