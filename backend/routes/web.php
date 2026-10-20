<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Laravel sert le frontend React via Inertia; les données métier restent dans l'API JSON /api.
// Le fallback sert les pages React, jamais les URL /api inconnues : celles-ci doivent renvoyer un 404 JSON.
Route::get('/{path?}', fn () => Inertia::render('Application'))
    ->where('path', '(?!api(?:/|$)).*');
