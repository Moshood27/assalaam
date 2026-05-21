<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

class LogFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(protected Request $request)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        activity('auth')
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'email' => $event->credentials['email'] ?? ($event->credentials['phone'] ?? 'unknown'),
                'guard' => $event->guard,
            ])
            ->log('Failed login attempt');
    }
}
