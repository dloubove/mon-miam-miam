<?php

namespace App\Providers;

use App\Contracts\PaiementGateway;
use App\Services\PaiementAgregateurGateway;
use App\Services\PaiementCinetPayGateway;
use App\Services\PaiementSimuleGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaiementGateway::class, fn () => match (config('services.mobile_money.mode')) {
            'cinetpay' => new PaiementCinetPayGateway(),
            'aggregator' => new PaiementAgregateurGateway(),
            default => new PaiementSimuleGateway(),
        });
    }

    public function boot(): void
    {
        $this->configurerLimiteursDeDebit();

        // Les réponses des « Resources » ne sont pas enveloppées dans { "data": ... } :
        // le frontend reçoit directement l'objet, c'est plus simple à lire.
        JsonResource::withoutWrapping();
    }

    /**
     * Limites de requêtes par minute, contre la force brute et les abus.
     * Au-delà, l'API répond 429 « Trop de tentatives ».
     */
    private function configurerLimiteursDeDebit(): void
    {
        // Connexion : 5 essais par minute pour un même couple email + adresse IP.
        RateLimiter::for('connexion', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Création de compte : 10 par minute et par adresse IP.
        RateLimiter::for('inscription', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // « Mot de passe oublié » / réinitialisation : 5 par minute et par IP (évite le spam d'emails).
        RateLimiter::for('mot-de-passe', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Limite générale de l'API : 120 requêtes/minute, par utilisateur connecté (sinon par IP).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id_utilisateur ?: $request->ip()));
    }
}
