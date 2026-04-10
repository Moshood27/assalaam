<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            // Only update once per hour to avoid too many DB writes
            if (!$user->last_activity_at || $user->last_activity_at->diffInHours(now()) >= 1) {
                $user->updateQuietly(['last_activity_at' => now()]);
            }
        }
        return $next($request);
    }
}
