<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind KYC verifier as a singleton for cleaner resolution and future extension
        $this->app->singleton(\App\Services\Kyc\KycVerifier::class, function () {
            return new \App\Services\Kyc\KycVerifier();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global API rate limiter
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();
            return [
                Limit::perMinute(60)->by($key),
            ];
        });

        // Stricter limiter for login endpoints to mitigate brute force
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
            ];
        });
    }
}
