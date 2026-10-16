<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();

    $middleware->redirectGuestsTo(
        fn (Illuminate\Http\Request $request) =>
            $request->expectsJson() ? null : '/api/login'
    );

withExceptionHandling(function (Exceptions $exceptions): void {
    $exceptions->report(fn (Throwable $e) => false);
});

});
