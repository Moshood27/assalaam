<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogSuccessfulLogout
{
    public function __construct(protected Request $request) {}

    public function handle(Logout $event): void
    {
        if (!$event->user) {
            return;
        }

        activity('auth')
            ->performedOn($event->user)
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'guard' => $event->guard,
            ])
            ->log("User logged out");
    }
}
