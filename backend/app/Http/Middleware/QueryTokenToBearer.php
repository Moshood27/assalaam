<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QueryTokenToBearer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the 'token' query parameter exists, use it as the Bearer token for authentication.
        // We also force the 'Accept' header to 'application/json' to ensure that if
        // authentication fails, the framework returns a JSON 401 response instead of
        // redirecting to a login page (which causes "Unauthenticated" text messages in browsers).
        if ($request->filled('token')) {
            $token = $request->query('token');

            if (!$request->bearerToken()) {
                 $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            // Ensure the request is treated as an AJAX/API request to avoid redirects
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $next($request);
    }
}
