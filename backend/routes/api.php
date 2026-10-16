
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['web', 'throttle:5,1']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'id' => $request->user()->id_utilisateur,
            'nom' => $request->user()->nom,
            'email' => $request->user()->email,
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});
