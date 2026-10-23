<?php

namespace App\Http\Controllers;

use App\Rules\MotDePasseSolide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** One-time, secret-protected setup page for the first production administrator. */
class BootstrapAdminController extends Controller
{
    public function show()
    {
        abort_unless($this->bootstrapToken(), 404);

        return view('bootstrap-administrateur', [
            'ferme' => $this->administrateurExiste(),
            'cree' => false,
        ]);
    }

    public function store(Request $request)
    {
        $token = $this->bootstrapToken();
        abort_unless($token, 404);
        abort_unless(hash_equals($token, (string) $request->input('jeton')), 403);
        abort_if($this->administrateurExiste(), 410, 'Un administrateur existe déjà.');

        $data = $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:utilisateur,email'],
            'telephone' => ['required', 'regex:/^[0-9+() -]{7,20}$/'],
            'mot_de_passe' => ['required', new MotDePasseSolide, 'confirmed'],
            'jeton' => ['required', 'string', 'max:255'],
        ], [
            'mot_de_passe.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        DB::transaction(function () use ($data): void {
            $role = DB::table('role')->where('libelle_role', 'administrateur')->lockForUpdate()->first();
            $restaurantId = DB::table('restaurant')->where('nom_restaurant', 'ZEDUCSP@CE')->value('id_restaurant');

            abort_unless($role && $restaurantId, 503, 'Les données de référence ne sont pas initialisées.');
            abort_if($this->administrateurExiste(), 410, 'Un administrateur existe déjà.');

            $id = DB::table('utilisateur')->insertGetId([
                'nom' => trim($data['nom']),
                'email' => mb_strtolower(trim($data['email'])),
                'telephone' => $data['telephone'],
                'mot_de_passe' => Hash::make($data['mot_de_passe']),
                'statut_compte' => 'actif',
                'email_verified_at' => now(),
            ], 'id_utilisateur');

            DB::table('personnel')->insert([
                'id_utilisateur' => $id,
                'matricule' => 'ADM-'.Str::upper(Str::random(12)),
                'date_embauche' => now()->toDateString(),
                'id_role' => $role->id_role,
                'id_restaurant' => $restaurantId,
                'id_createur' => null,
            ]);
        });

        return view('bootstrap-administrateur', ['ferme' => true, 'cree' => true]);
    }

    private function bootstrapToken(): ?string
    {
        $token = config('services.admin_bootstrap_token');

        return is_string($token) && strlen($token) >= 32 ? $token : null;
    }

    private function administrateurExiste(): bool
    {
        return DB::table('personnel as p')
            ->join('role as r', 'r.id_role', '=', 'p.id_role')
            ->where('r.libelle_role', 'administrateur')
            ->exists();
    }
}
