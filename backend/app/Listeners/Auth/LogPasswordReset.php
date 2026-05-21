<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;

class LogPasswordReset
{
    public function __construct(protected Request $request) {}

    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ])
            ->log("Password reset");
    }
}
