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
        // Register Filament Breezy components globally to avoid ComponentNotFoundException during Livewire updates
        \Livewire\Livewire::component('personal_info', \Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo::class);
        \Livewire\Livewire::component('update_password', \Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword::class);
        \Livewire\Livewire::component('two_factor_authentication', \Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication::class);
        \Livewire\Livewire::component('sanctum_tokens', \Jeffgreco13\FilamentBreezy\Livewire\SanctumTokens::class);
        \Livewire\Livewire::component('browser_sessions', \Jeffgreco13\FilamentBreezy\Livewire\BrowserSessions::class);
        \Livewire\Livewire::component('two-factor-page', \Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage::class);
        \Livewire\Livewire::component('admin-notification-listener', \App\Livewire\AdminNotificationListener::class);

        \App\Models\StoreOrder::observe(\App\Observers\StoreOrderObserver::class);
        \App\Models\SadaqahProject::observe(\App\Observers\SadaqahProjectObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\IncomeEntry::observe(\App\Observers\IncomeEntryObserver::class);
        \App\Models\ExpenseEntry::observe(\App\Observers\ExpenseEntryObserver::class);
        \App\Models\CharityEntry::observe(\App\Observers\CharityEntryObserver::class);
        \App\Models\WalletTransaction::observe(\App\Observers\WalletTransactionObserver::class);
        \App\Models\Contribution::observe(\App\Observers\ContributionObserver::class);
        \App\Models\QardHasan::observe(\App\Observers\QardHasanObserver::class);
        \App\Models\QardHasanRepayment::observe(\App\Observers\QardHasanRepaymentObserver::class);

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
                Limit::perMinute(5)->by($request->ip()),
            ];
        });
    }
}
