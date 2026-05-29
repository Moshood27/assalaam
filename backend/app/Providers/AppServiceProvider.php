<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use App\Listeners\Auth\LogSuccessfulLogin;
use App\Listeners\Auth\LogSuccessfulLogout;
use App\Listeners\Auth\LogFailedLogin;
use App\Listeners\Auth\LogLockout;
use App\Listeners\Security\LogRoleChange;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;

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
        Feature::define('withdrawals-enabled', fn () => true);
        Feature::define('payment-provider-failover', fn () => false);
        Feature::define('maintenance-mode-wallets', fn () => false);
        Feature::define('gold-savings-beta', function ($scope) {
            if ($scope instanceof User) {
                if (Feature::for('global')->inactive('gold-savings-beta')) {
                    return false;
                }
                return ($scope->assalaam_score ?? 0) > 80;
            }
            return true;
        });
        Feature::define('apply-for-loan', function ($scope) {
            if ($scope instanceof User) {
                if (Feature::for('global')->inactive('apply-for-loan')) {
                    return false;
                }
                return $scope->is_verified && ($scope->assalaam_score ?? 0) > 40;
            }
            return true;
        });
        Feature::define('shura-voting-active', fn () => false);
        Feature::define('prayer-time-quiet-mode', fn () => false);
        Feature::define('gender-segregated-features', function ($scope) {
            if ($scope instanceof User) {
                // Example: Only show if user gender matches or is not strictly segregated
                return true;
            }
            return true;
        });
        Feature::define('show-flw-balance', function ($scope) {
            if ($scope instanceof User) {
                if (Feature::for('global')->inactive('show-flw-balance')) {
                    return false;
                }
            }
            return config('services.flutterwave.compliance_status') === 'approved';
        });

        Feature::define('takaful-enabled', fn () => true);
        Feature::define('gold-savings-enabled', fn () => true);
        Feature::define('group-savings-enabled', fn () => true);
        Feature::define('receive-qr-enabled', fn () => true);
        Feature::define('merchant-pay-enabled', fn () => true);
        Feature::define('zakat-enabled', fn () => true);
        Feature::define('junior-coop-enabled', fn () => true);
        Feature::define('projects-enabled', fn () => true);
        Feature::define('chat-help-enabled', fn () => true);

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

        // Register Auth Event Listeners for Activity Logging
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Lockout::class, LogLockout::class);

        // Register Security Event Listeners
        Event::listen(RoleAttached::class, LogRoleChange::class);
        Event::listen(RoleDetached::class, LogRoleChange::class);
        Event::listen(PermissionAttached::class, LogRoleChange::class);
        Event::listen(PermissionDetached::class, LogRoleChange::class);
    }
}
