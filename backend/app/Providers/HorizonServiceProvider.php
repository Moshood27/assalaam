<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                'admin@attaqwa.com'
                //
            ]);
        });
    }
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night(); // Optional: Dark mode

        //$this->hideSensitiveAttributes();

        Telescope::filter(function (IncomingEntry $entry) {
            // In local, record everything
            if ($this->app->environment('local')) {
                return true;
            }

            // In production, record logs, failed jobs, and scheduled tasks
            return $entry->isReportableException() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag() ||
                $entry->type === 'log'; // <--- ENSURE THIS IS HERE
        });
    }
}
