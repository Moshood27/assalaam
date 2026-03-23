<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust proxies (e.g., ngrok) so Laravel honors X-Forwarded-* headers
        $middleware->trustProxies(at: '*');

        // Exclude Paystack webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/paystack',
        ]);

        // Alias custom middleware
        $middleware->alias([
            'inactivity' => \App\Http\Middleware\InactivityTimeout::class,
        ]);

        // Append security headers to all web and API responses
        $middleware->appendToGroup('web', [\App\Http\Middleware\SecurityHeaders::class]);
        $middleware->appendToGroup('api', [\App\Http\Middleware\SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
