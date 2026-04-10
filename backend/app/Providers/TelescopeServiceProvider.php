<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */


    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'x-paystack-signature',
            'x-flutterwave-signature',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            if ($isLocal) {
                return true;
            }

            return $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag() ||
                $entry->type === 'log' ||
                $entry->type === 'job' ||
                ($entry->type === 'request' && (str_contains($entry->content['uri'] ?? '', 'webhook') || str_contains($entry->content['uri'] ?? '', 'callback')));
        });

        Telescope::tag(function (IncomingEntry $entry) {
            if ($entry->type === 'request' && (str_contains($entry->content['uri'] ?? '', 'webhook') || str_contains($entry->content['uri'] ?? '', 'callback'))) {
                return ['webhook'];
            }

            return [];
        });
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            $emails = array_map('trim', explode(',', env('TELESCOPE_EMAILS', 'admin@attaqwa.com')));
            return in_array($user->email, $emails);
        });
    }
}
