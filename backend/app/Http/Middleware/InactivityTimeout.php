<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InactivityTimeout
{
    /**
     * Max allowed inactivity in seconds.
     */
    protected int $timeout = 120; // 2 minutes

    public function __construct()
    {
        $this->timeout = (int) env('INACTIVITY_TIMEOUT_SECONDS', 120);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only enforce for Sanctum authenticated requests with a personal access token
        if ($user && method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();

            if ($token) {
                $lastUsed = $token->last_used_at ?? $token->created_at; // fallback to creation time

                // Enforce inactivity timeout only if it's NOT a 'remember_token'
                if ($token->name !== 'remember_token') {
                    if ($lastUsed && now()->diffInSeconds($lastUsed) > $this->timeout) {
                        // Revoke token and reject request
                        $token->delete();

                        return response()->json([
                            'message' => 'Session expired due to inactivity.',
                        ], 401);
                    }
                }

                // Proceed with request, then stamp last_used_at after successful handling
                /** @var Response $response */
                $response = $next($request);

                // Update last_used_at to now for future checks
                try {
                    $token->forceFill(['last_used_at' => now()])->save();
                } catch (\Throwable $e) {
                    // Silently ignore if DB is read-only or any unexpected error occurs
                }

                return $response;
            }
        }

        return $next($request);
    }
}
