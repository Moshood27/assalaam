<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $ttl  Time to live in seconds
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, int $ttl = 60): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Create a unique cache key based on the URL and user (if authenticated)
        $user = $request->user();
        $key = 'api_cache:' . md5($request->fullUrl() . ($user ? $user->id : 'guest'));

        // Try to get from cache
        if (Cache::has($key)) {
            $cached = Cache::get($key);

            // Add a header to indicate cache hit
            $response = response($cached['content'], $cached['status']);
            foreach ($cached['headers'] as $name => $values) {
                $response->header($name, $values);
            }
            $response->header('X-Cache', 'HIT');

            return $response;
        }

        // Get the response
        $response = $next($request);

        // Only cache successful responses
        if ($response->isSuccessful()) {
            $headers = $response->headers->all();
            // Remove some headers that shouldn't be cached
            unset($headers['set-cookie']);

            Cache::put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $headers,
            ], $ttl);

            $response->header('X-Cache', 'MISS');
        }

        return $response;
    }
}
