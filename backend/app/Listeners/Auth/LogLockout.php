<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;

class LogLockout
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
    public function handle(Lockout $event): void
    {
        activity('security')
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'email' => $event->request->email ?? ($event->request->phone ?? 'unknown'),
            ])
            ->log('User account locked out due to multiple failed attempts');
    }
}
