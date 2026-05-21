<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LogFailedLogin
{
    public function __construct(protected Request $request) {}

    public function handle(Failed $event): void
    {
        $ip = $this->request->ip();
        $email = $event->credentials['email'] ?? 'unknown';

        activity('auth')
            ->withProperties([
                'ip' => $ip,
                'user_agent' => $this->request->userAgent(),
                'email' => $email,
                'guard' => $event->guard,
            ])
            ->log("Failed login attempt");

        // Suspicious action detection: Multiple failed logins from same IP
        $cacheKey = "failed_logins_{$ip}";
        $attempts = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $attempts, now()->addHour());

        if ($attempts >= 5) {
            activity('suspicious')
                ->withProperties([
                    'ip' => $ip,
                    'attempts' => $attempts,
                    'email' => $email,
                ])
                ->log("Multiple failed login attempts detected");
        }
    }
}
