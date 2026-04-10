<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\WhitelistedIp;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class IpWhitelistMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staticWhitelist = config('cooperative.admin_ip_whitelist', []);
        $dbWhitelist = [];

        // Try to get whitelisted IPs from database if table exists
        try {
            if (Schema::hasTable('whitelisted_ips')) {
                $dbWhitelist = WhitelistedIp::where('is_active', true)->pluck('ip_address')->toArray();
            }
        } catch (\Throwable $e) {
            // Fallback to empty if DB is not reachable
        }

        $fullWhitelist = array_unique(array_merge($staticWhitelist, $dbWhitelist));

        // If both whitelists are empty, allow all requests (safety feature)
        if (empty($fullWhitelist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Always allow localhost/loopback in development or if explicitly allowed
        if (app()->environment('local') && in_array($clientIp, ['127.0.0.1', '::1'])) {
            return $next($request);
        }

        // Check if the client IP is in the whitelist (exact match)
        if (in_array($clientIp, $fullWhitelist)) {
            $this->updateLastUsed($clientIp);
            return $next($request);
        }

        // Check for CIDR notation matches
        foreach ($fullWhitelist as $allowedIp) {
            if ($this->ipInNetwork($clientIp, $allowedIp)) {
                $this->updateLastUsed($allowedIp);
                return $next($request);
            }
        }

        Log::warning("Unauthorized IP address access attempt: {$clientIp} on " . $request->fullUrl());

        abort(403, 'Unauthorized IP address: ' . $clientIp);
    }

    /**
     * Update the last_used_at timestamp for a database record if it exists.
     */
    protected function updateLastUsed(string $ip): void
    {
        try {
            if (Schema::hasTable('whitelisted_ips')) {
                WhitelistedIp::where('ip_address', $ip)
                    ->where('is_active', true)
                    ->update(['last_used_at' => now()]);
            }
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    /**
     * Check if an IP address is in a network range (CIDR notation supported).
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    protected function ipInNetwork(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($range, $netmask) = explode('/', $range, 2);

        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);

        // If ip2long fails (e.g. for IPv6), fallback to simple string match
        // Note: Simple ip2long doesn't support IPv6.
        // For now, we'll stick to IPv4 for CIDR check or simple string match for IPv6.
        if ($range_decimal === false || $ip_decimal === false) {
            return $ip === $range;
        }

        $wildcard_decimal = pow(2, (32 - (int)$netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
