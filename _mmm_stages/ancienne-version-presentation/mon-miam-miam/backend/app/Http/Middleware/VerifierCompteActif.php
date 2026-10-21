<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Refuse l’accès aux comptes désactivés, après l’authentification Sanctum. */
class VerifierCompteActif
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->estActif()) {
            return response()->json(['message' => 'Ce compte est désactivé.'], 401);
        }

        return $next($request);
    }
}
