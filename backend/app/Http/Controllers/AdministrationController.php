<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdministrationController extends Controller
{
    public function reglesFidelite()
    {
        $restaurant = DB::table('restaurant')->orderBy('id_restaurant')->value('id_restaurant');
        return response()->json(DB::table('parametre')->where('id_restaurant', $restaurant)
            ->whereIn('cle_parametre', ['points_par_1000_fcfa', 'points_par_tranche_reduction', 'fcfa_pour_15_points', 'seuil_min_points_utilisation', 'duree_validite_points_mois'])
            ->pluck('valeur_parametre', 'cle_parametre')->map(fn ($value) => (int) $value));
    }

    public function tableauDeBord()
    {
        $debutJour = now()->startOfDay();
        $debutMois = now()->startOfMonth();
        $restaurant = DB::table('restaurant')->orderBy('id_restaurant')->value('id_restaurant');
        $metrics = [
            'utilisateurs' => DB::table('utilisateur')->where('statut_compte', 'actif')->count(),
            'etudiants' => DB::table('etudiant')->count(),
            'commandes_en_cours' => DB::table('commande')->where('id_restaurant', $restaurant)->whereIn('statut_commande', ['en_attente', 'en_preparation', 'prete'])->count(),
            'commandes_payees' => DB::table('paiement as p')->join('commande as c', 'c.id_commande', '=', 'p.id_commande')
                ->where('p.statut_paiement', 'reussi')->where('c.id_restaurant', $restaurant)->count(),
            'chiffre_affaires_fcfa' => (int) DB::table('paiement as p')->join('commande as c', 'c.id_commande', '=', 'p.id_commande')
                ->where('p.statut_paiement', 'reussi')->where('c.id_restaurant', $restaurant)->where('p.date_paiement', '>=', $debutJour)->sum('p.montant_paye'),
            'commandes_aujourdhui' => DB::table('commande')->where('id_restaurant', $restaurant)->where('date_commande', '>=', $debutJour)->whereNotIn('statut_commande', ['annulee', 'panier'])->count(),
            'points_utilises' => abs((int) DB::table('mouvement_points as mp')->join('commande as c', 'c.id_commande', '=', 'mp.id_commande')
                ->where('c.id_restaurant', $restaurant)->where('mp.type_mouvement', 'utilisation')->where('mp.date_mouvement', '>=', $debutJour)->sum('mp.nombre_points')),
            'reduction_points_fcfa' => (int) DB::table('commande')->where('id_restaurant', $restaurant)->where('date_commande', '>=', $debutJour)->sum('reduction_points'),
            'reclamations_ouvertes' => DB::table('reclamation as r')->leftJoin('commande as c', 'c.id_commande', '=', 'r.id_commande')
                ->where(function ($query) use ($restaurant) { $query->where('c.id_restaurant', $restaurant)->orWhereNull('r.id_commande'); })
                ->whereIn('r.statut_reclamation', ['ouverte', 'repondue'])->count(),
            'plats_populaires' => DB::table('contenir as l')->join('commande as c', 'c.id_commande', '=', 'l.id_commande')
                ->join('article as a', 'a.id_article', '=', 'l.id_article')->join('categorie as cat', 'cat.id_categorie', '=', 'a.id_categorie')
                ->where('c.id_restaurant', $restaurant)->where('c.date_commande', '>=', $debutMois)
                ->whereIn('c.statut_commande', ['en_attente', 'en_preparation', 'prete', 'servie', 'livree'])
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('paiement as p')->whereColumn('p.id_commande', 'c.id_commande')->where('p.statut_paiement', 'reussi'))
                ->select('a.nom_article as name', 'cat.libelle_categorie as category', DB::raw('SUM(l.quantite)::integer as sales'))
                ->groupBy('a.nom_article', 'cat.libelle_categorie')->orderByDesc('sales')->limit(10)->get(),
        ];
        return response()->json($metrics);
    }

    /** Top clients basé sur les commandes du restaurant et une période sélectionnée. */
    public function topClients(Request $request)
    {
        $data = $request->validate(['periode' => ['sometimes', Rule::in(['jour', 'semaine', 'mois'])]]);
        $debut = match ($data['periode'] ?? 'mois') {
            'jour' => now()->startOfDay(),
            'semaine' => now()->startOfWeek(),
            default => now()->startOfMonth(),
        };
        $restaurant = DB::table('restaurant')->orderBy('id_restaurant')->value('id_restaurant');
        return DB::table('commande as c')->join('etudiant as e', 'e.id_utilisateur', '=', 'c.id_etudiant')
            ->join('utilisateur as u', 'u.id_utilisateur', '=', 'e.id_utilisateur')
            ->where('c.id_restaurant', $restaurant)->where('c.date_commande', '>=', $debut)
            ->whereIn('c.statut_commande', ['en_attente', 'en_preparation', 'prete', 'servie', 'livree'])
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('paiement as p')->whereColumn('p.id_commande', 'c.id_commande')->where('p.statut_paiement', 'reussi'))
            ->select('u.id_utilisateur as id', 'u.nom as name', DB::raw('COUNT(*)::integer as orders'), DB::raw('SUM(c.montant_total)::integer as spent'))
            ->groupBy('u.id_utilisateur', 'u.nom')->orderByDesc('orders')->orderByDesc('spent')->limit(10)->get();
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
