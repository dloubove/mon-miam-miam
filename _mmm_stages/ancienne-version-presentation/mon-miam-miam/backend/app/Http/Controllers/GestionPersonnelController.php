<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Services\AuthService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Gestion des comptes du restaurant, avec désactivation afin de préserver les historiques. */
class GestionPersonnelController extends Controller
{
    public function __construct(private readonly RestaurantService $restaurants, private readonly AuthService $auth) {}

    public function index(Request $request): JsonResponse
    {
        $restaurant = $this->restaurantDuCompte($request->user());
        $rows = DB::table('personnel as p')->join('utilisateur as u', 'u.id_utilisateur', '=', 'p.id_utilisateur')
            ->join('role as r', 'r.id_role', '=', 'p.id_role')->where('p.id_restaurant', $restaurant)
            ->select('u.id_utilisateur as id', 'u.nom as name', 'u.email', 'u.telephone', 'u.statut_compte as status', 'r.libelle_role as role', 'p.matricule')
            ->orderBy('r.libelle_role')->orderBy('u.nom')->get();
        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $isAdmin = $request->user()->role === 'administrateur';
        $data = $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:utilisateur,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'role' => ['required', Rule::in($isAdmin ? ['employe', 'gerant', 'administrateur'] : ['employe'])],
        ]);
        $created = DB::transaction(function () use ($data, $request) {
            $roleId = DB::table('role')->where('libelle_role', $data['role'])->value('id_role');
            abort_unless($roleId, 422, 'Rôle non configuré. Lancez les seeders de référence.');
            $user = Utilisateur::create([
                'nom' => trim($data['nom']), 'email' => mb_strtolower(trim($data['email'])),
                'telephone' => $data['telephone'], 'mot_de_passe' => Str::random(48),
            ]);
            DB::table('personnel')->insert([
                'id_utilisateur' => $user->id_utilisateur,
                'matricule' => 'MMM-'.str_pad((string) $user->id_utilisateur, 6, '0', STR_PAD_LEFT),
                'date_embauche' => now()->toDateString(), 'id_role' => $roleId,
                'id_restaurant' => $this->restaurantDuCompte($request->user()),
                'id_createur' => $request->user()->id_utilisateur,
            ]);
            return $user;
        });

        // L'accès est créé même si le serveur SMTP est momentanément indisponible.
        try { $this->auth->demanderReinitialisation($created->email); } catch (\Throwable $error) { report($error); }
        return response()->json(['id' => $created->id_utilisateur, 'message' => 'Compte créé. Un lien de définition du mot de passe a été envoyé si le mail est configuré.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->role === 'administrateur', 403, 'Seul l’administrateur peut modifier les comptes du personnel.');
        $personnel = DB::table('personnel')->where('id_utilisateur', $id)->first();
        abort_unless($personnel, 404);
        $data = $request->validate([
            'nom' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('utilisateur', 'email')->ignore($id, 'id_utilisateur')],
            'telephone' => ['sometimes', 'required', 'string', 'max:20'],
            'role' => ['sometimes', 'required', Rule::in(['employe', 'gerant', 'administrateur'])],
            'actif' => ['sometimes', 'required', 'boolean'],
        ]);
        DB::transaction(function () use ($data, $id) {
            $userFields = array_intersect_key($data, array_flip(['nom', 'email', 'telephone']));
            if (array_key_exists('actif', $data)) $userFields['statut_compte'] = $data['actif'] ? 'actif' : 'inactif';
            if ($userFields) DB::table('utilisateur')->where('id_utilisateur', $id)->update($userFields);
            if (isset($data['role'])) {
                $roleId = DB::table('role')->where('libelle_role', $data['role'])->value('id_role');
                abort_unless($roleId, 422, 'Rôle non configuré.');
                DB::table('personnel')->where('id_utilisateur', $id)->update(['id_role' => $roleId]);
            }
        });
        return response()->json(['message' => 'Compte mis à jour.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->role === 'administrateur', 403, 'Seul l’administrateur peut désactiver un compte.');
        abort_if($request->user()->id_utilisateur === $id, 422, 'Vous ne pouvez pas désactiver votre propre compte.');
        $updated = DB::table('utilisateur')->where('id_utilisateur', $id)->whereExists(function ($query) use ($id) {
            $query->selectRaw('1')->from('personnel')->where('personnel.id_utilisateur', $id);
        })->update(['statut_compte' => 'inactif']);
        abort_unless($updated, 404);
        DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();
        return response()->json(['message' => 'Compte désactivé. Son historique est conservé.']);
    }

    private function restaurantDuCompte(Utilisateur $user): int
    {
        return (int) (DB::table('personnel')->where('id_utilisateur', $user->id_utilisateur)->value('id_restaurant')
            ?: $this->restaurants->id());
    }
}
