<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogSuccessfulLogin
{
    public function __construct(protected Request $request) {}

    public function handle(Login $event): void
    {
        $user = $event->user;
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => $ip,
                'user_agent' => $userAgent,
                'guard' => $event->guard,
            ])
            ->log("User logged in");
    }
}
