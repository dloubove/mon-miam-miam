<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BootstrapAdminController;

// First-admin setup is available only while a secret is configured and no admin exists.
Route::get('/initialiser-administrateur', [BootstrapAdminController::class, 'show'])
    ->middleware('throttle:10,1');
Route::post('/initialiser-administrateur', [BootstrapAdminController::class, 'store'])
    ->middleware('throttle:5,1');

// Laravel sert le frontend React via Inertia; les données métier restent dans l'API JSON /api.
// Le fallback sert les pages React, jamais les URL /api inconnues : celles-ci doivent renvoyer un 404 JSON.
Route::get('/{path?}', fn () => Inertia::render('Application'))
    ->where('path', '(?!api(?:/|$)).*');
