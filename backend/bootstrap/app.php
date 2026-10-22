<?php

use App\Exceptions\RegleMetierException;
use App\Http\Middleware\VerifierRole;
use App\Http\Middleware\VerifierCompteActif;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Fichier des routes de l'API : elles sont toutes préfixées par /api automatiquement.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render termine TLS devant l'application et transmet le protocole HTTPS via X-Forwarded-Proto.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [HandleInertiaRequests::class]);
        // Limite globale de requêtes sur l'API (voir RateLimiter::for('api') dans AppServiceProvider).
        $middleware->throttleApi();

        // Un visiteur non connecté ne doit JAMAIS être redirigé vers une page « login » :
        // cette API n'a pas de pages web. En renvoyant null, Laravel répond simplement 401.
        $middleware->redirectGuestsTo(fn () => null);

        // Raccourci utilisable dans les routes : ->middleware('role:gerant,administrateur')
        $middleware->alias([
            'role' => VerifierRole::class,
            'compte.actif' => VerifierCompteActif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pour toutes les URL /api/*, les erreurs sont TOUJOURS renvoyées en JSON
        // (jamais de page HTML ni de redirection vers une page de connexion).
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // 401 : pas de jeton, jeton invalide, expiré ou révoqué.
        $exceptions->render(fn (AuthenticationException $e) => response()->json(
            ['message' => 'Authentification requise : connectez-vous pour continuer.'], 401));

        // 429 : trop de tentatives (limiteur de débit). On garde l'en-tête Retry-After.
        $exceptions->render(fn (ThrottleRequestsException $e) => response()->json(
            ['message' => 'Trop de tentatives. Réessayez dans quelques instants.'], 429, $e->getHeaders()));

        // Règle métier violée (commande déjà en préparation, solde de points insuffisant…) :
        // message clair + code 409 (conflit avec l'état actuel), 422 ou 403 selon le cas.
        $exceptions->render(fn (RegleMetierException $e) => response()->json(['message' => $e->getMessage()], $e->statutHttp));

        // 404 : route ou ressource inexistante.
        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Ressource introuvable.'], 404)
            : null);
    })->create();
