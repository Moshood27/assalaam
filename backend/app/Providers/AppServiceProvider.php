<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use App\Models\User;

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
        // Define Feature Flags
        Feature::define('withdrawals-enabled', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('withdrawals-enabled');
            }
            return true; // Default to enabled
        });

        Feature::define('payment-provider-failover', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('payment-provider-failover');
            }
            return false; // Default to normal operation (no failover)
        });

        Feature::define('maintenance-mode-wallets', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('maintenance-mode-wallets');
            }
            return false; // Default to normal operation (no maintenance)
        });

        Feature::define('gold-savings-beta', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('gold-savings-beta');
            }
            return true; // Default to enabled as requested
        });

        Feature::define('apply-for-loan', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('apply-for-loan');
            }
            return true; // Default to enabled as requested
        });

        Feature::define('shura-voting-active', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('shura-voting-active');
            }
            return false; // Event-based: default to off
        });

        Feature::define('prayer-time-quiet-mode', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('prayer-time-quiet-mode');
            }
            return false; // Event-based: default to off
        });

        Feature::define('gender-segregated-features', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('gender-segregated-features');
            }
            return true;
        });

        Feature::define('show-flw-balance', function ($scope) {
            if ($scope instanceof User) {
                return Feature::for('global')->active('show-flw-balance');
            }
            // Fallback to compliance status for global/other if not in DB
            return config('services.flutterwave.compliance_status') === 'approved';
        });

        // Register Filament Breezy components globally to avoid ComponentNotFoundException during Livewire updates
        \Livewire\Livewire::component('personal_info', \Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo::class);
        \Livewire\Livewire::component('update_password', \Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword::class);
        \Livewire\Livewire::component('two_factor_authentication', \Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication::class);
        \Livewire\Livewire::component('sanctum_tokens', \Jeffgreco13\FilamentBreezy\Livewire\SanctumTokens::class);
        \Livewire\Livewire::component('browser_sessions', \Jeffgreco13\FilamentBreezy\Livewire\BrowserSessions::class);
        \Livewire\Livewire::component('two-factor-page', \Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage::class);
        \Livewire\Livewire::component('admin-notification-listener', \App\Livewire\AdminNotificationListener::class);

        \App\Models\StoreOrder::observe(\App\Observers\StoreOrderObserver::class);
        \App\Models\ProjectProfit::observe(\App\Observers\ProjectProfitObserver::class);
        \App\Models\ProjectProfitPayout::observe(\App\Observers\ProjectProfitPayoutObserver::class);
        \App\Models\SadaqahProject::observe(\App\Observers\SadaqahProjectObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\IncomeEntry::observe(\App\Observers\IncomeEntryObserver::class);
        \App\Models\ExpenseEntry::observe(\App\Observers\ExpenseEntryObserver::class);
        \App\Models\CharityEntry::observe(\App\Observers\CharityEntryObserver::class);
        \App\Models\WalletTransaction::observe(\App\Observers\WalletTransactionObserver::class);
        \App\Models\Contribution::observe(\App\Observers\ContributionObserver::class);
        \App\Models\QardHasan::observe(\App\Observers\QardHasanObserver::class);
        \App\Models\QardHasanRepayment::observe(\App\Observers\QardHasanRepaymentObserver::class);

        // Global API rate limiter with burst capability
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $key = $user ? $user->id : $request->ip();

            // Higher limits for verified members to ensure good UX
            if ($user && $user->is_verified) {
                return [
                    Limit::perMinute(120)->by($key),
                ];
            }

            return [
                Limit::perMinute(60)->by($key),
            ];
        });

        // Stricter limiter for high-cost data aggregation endpoints (e.g., reports, legacy passbook)
        RateLimiter::for('heavy', function (Request $request) {
            return [
                Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip()),
            ];
        });

        // Stricter limiter for login and sensitive auth actions
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(10)->by($request->input('email', $request->ip())),
            ];
        });

        // Rate limiter for webhooks to prevent flooding from a single source
        RateLimiter::for('webhooks', function (Request $request) {
            return [
                Limit::perMinute(300)->by($request->ip()),
            ];
        });
    }
}
