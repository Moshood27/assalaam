<?php

use App\Services\SecurityLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware' => ['auth:sanctum,web']])
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust proxies (e.g., ngrok) so Laravel honors X-Forwarded-* headers
        $middleware->trustProxies(at: '*');

        // Exclude Paystack webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/paystack',
        ]);

        // Alias custom middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'inactivity' => \App\Http\Middleware\InactivityTimeout::class,
            'track_activity' => \App\Http\Middleware\TrackUserActivity::class,
        ]);

        // Append security headers to all web and API responses
        $middleware->appendToGroup('web', [\App\Http\Middleware\SecurityHeaders::class]);
        $middleware->prependToGroup('api', [\App\Http\Middleware\QueryTokenToBearer::class]);
        $middleware->appendToGroup('api', [\App\Http\Middleware\SecurityHeaders::class]);
        $middleware->appendToGroup('api', [\App\Http\Middleware\TrackUserActivity::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (AccessDeniedHttpException $e) {
            SecurityLogger::logUnauthorizedAccess(request()->fullUrl());
        });

        if (class_exists(\Sentry\Laravel\Integration::class)) {
            \Sentry\Laravel\Integration::handles($exceptions);
        }
    })->create();
