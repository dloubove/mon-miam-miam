<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (
            !$user ||
            $user->statut_compte !== 'actif' ||
            !Hash::check($credentials['password'], $user->mot_de_passe)
        ) {
            return response()->json([
                'message' => 'Identifiants incorrects ou compte inactif.',
            ], 422);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => [
                'id' => $user->id_utilisateur,
                'nom' => $user->nom,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
