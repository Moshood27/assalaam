<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;

class LogLockout
{
    public function __construct(protected Request $request) {}

    public function handle(Lockout $event): void
    {
        $email = $event->request->input('email') ?? 'unknown';

        activity('suspicious')
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'email' => $email,
            ])
            ->log("User account locked out due to too many failed attempts");
    }
}
