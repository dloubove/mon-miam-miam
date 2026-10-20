<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à certains rôles.
 *
 * Utilisation dans routes/api.php :
 *     Route::get('...')->middleware(['auth:sanctum', 'role:gerant,administrateur']);
 * Doit toujours venir APRÈS `auth:sanctum` (il faut un utilisateur identifié).
 */
class VerifierRole
{
    public function handle(Request $request, Closure $next, string ...$rolesAutorises): Response
    {
        $role = $request->user()?->role;

        // 403 = « je sais qui vous êtes, mais vous n'avez pas le droit ».
        if (! $role || ! in_array($role, $rolesAutorises, true)) {
            return response()->json(['message' => "Vous n'avez pas les droits pour cette action."], 403);
        }

        return $next($request);
    }
}
