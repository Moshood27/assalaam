<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurityController extends Controller
{
    /**
     * Set or change 4-digit Transaction PIN (requires current password).
     */
    public function setPin(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_pin' => ['required','regex:/^\\d{4}$/'],
            'confirm_pin' => 'required|same:new_pin',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 403);
        }

        $user->transaction_pin_hash = Hash::make($validated['new_pin']);
        $user->pin_set_at = now();
        $user->save();

        return response()->json(['message' => 'Transaction PIN set successfully']);
    }

    /**
     * Verify a submitted PIN; returns 200 if ok, 403 if invalid, 409 if not set.
     */
    public function verifyPin(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'pin' => ['required','regex:/^\\d{4}$/'],
        ]);

        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }

        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Request a one-time code to reset Transaction PIN (when forgotten).
     * Sends a 6-digit OTP via SMS (preferred) or email. Code valid for 10 minutes.
     */
    public function requestPinReset(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'channel' => 'nullable|in:sms,email',
        ]);

        $channel = $request->input('channel');
        $code = (string) random_int(100000, 999999);
        $cacheKey = 'pin_reset_'.$user->id;

        Cache::put($cacheKey, [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));

        $sentTo = null;
        $message = 'Your Transaction PIN reset code is '.$code.'. It expires in 10 minutes.';

        try {
            // Prefer SMS if phone exists and channel is not forced to email
            $phone = trim((string) ($user->phone ?? ''));
            if (($channel !== 'email') && $phone) {
                $sms = app(\App\Services\SmsService::class);
                $sms->send($phone, $message);
                $sentTo = self::maskPhone($phone);
            }
        } catch (\Throwable $e) {
            Log::warning('PIN reset SMS send failed', ['error' => $e->getMessage()]);
        }

        if (!$sentTo && (($channel !== 'sms') && !empty($user->email))) {
            try {
                Mail::raw($message, function ($m) use ($user) {
                    $m->to($user->email)->subject('Transaction PIN Reset Code');
                });
                $sentTo = self::maskEmail($user->email);
            } catch (\Throwable $e) {
                Log::warning('PIN reset email send failed', ['error' => $e->getMessage()]);
            }
        }

        // Do not disclose whether message actually delivered; return masked destination if available
        return response()->json([
            'message' => 'If your contact is on file, a reset code has been sent. The code expires in 10 minutes.',
            'sent_to' => $sentTo,
            'expires_in' => 600,
        ]);
    }

    /**
     * Confirm OTP and set a new 4-digit Transaction PIN without requiring account password.
     */
    public function confirmPinReset(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'code' => ['required','regex:/^\\d{6}$/'],
            'new_pin' => ['required','regex:/^\\d{4}$/'],
            'confirm_pin' => 'required|same:new_pin',
        ]);

        $cacheKey = 'pin_reset_'.$user->id;
        $payload = Cache::get($cacheKey);
        if (!$payload || empty($payload['hash'])) {
            return response()->json(['message' => 'Reset code expired or not requested'], 422);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if (!Hash::check($validated['code'], $payload['hash'])) {
            $attempts++;
            if ($attempts >= 5) {
                Cache::forget($cacheKey);
                return response()->json(['message' => 'Too many invalid attempts. Please request a new code.'], 429);
            }
            Cache::put($cacheKey, [
                'hash' => $payload['hash'],
                'attempts' => $attempts,
            ], now()->addMinutes(10));
            return response()->json(['message' => 'Invalid code'], 403);
        }

        // Valid code — set new PIN
        $user->transaction_pin_hash = Hash::make($validated['new_pin']);
        $user->pin_set_at = now();
        $user->save();

        Cache::forget($cacheKey);

        return response()->json(['message' => 'Transaction PIN reset successfully']);
    }

    protected static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $len = strlen($digits);
        if ($len <= 4) return str_repeat('*', max(0, $len - 2)).substr($digits, -2);
        return str_repeat('*', max(0, $len - 4)).substr($digits, -4);
    }

    protected static function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) return '***';
        $name = $parts[0];
        $domain = $parts[1];
        $show = max(1, (int) floor(strlen($name) * 0.3));
        return substr($name, 0, $show).str_repeat('*', max(0, strlen($name) - $show)).'@'.$domain;
    }
}
