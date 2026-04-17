<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

trait VerifiesOtp
{
    /**
     * Verify an OTP code for a given transaction type.
     *
     * @param \App\Models\User $user
     * @param string $type
     * @param string|null $code
     * @return bool
     */
    protected function verifyOtp($user, string $type, ?string $code): bool
    {
        if (!$code) {
            return false;
        }

        $cacheKey = 'otp_auth_' . $type . '_' . $user->id;
        $payload = Cache::get($cacheKey);

        if (!$payload || empty($payload['hash'])) {
            return false;
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            return false;
        }

        if (!Hash::check($code, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($cacheKey, $payload, now()->addMinutes(10));
            return false;
        }

        Cache::forget($cacheKey);
        return true;
    }
}
