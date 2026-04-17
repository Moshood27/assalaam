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
use App\Notifications\OtpNotification;

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

        $channel = $request->input('channel', 'sms');
        $code = (string) random_int(100000, 999999);
        $cacheKey = 'pin_reset_'.$user->id;

        Cache::put($cacheKey, [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));

        $message = 'Your Transaction PIN reset code is '.$code.'. It expires in 10 minutes.';

        try {
            $user->notify(new OtpNotification(
                title: 'PIN Reset Code Sent',
                message: $message,
                channel: $channel,
                context: [
                    'expires_in' => 600,
                ]
            ));
        } catch (\Throwable $e) {
            Log::warning('PIN reset notification failed', ['error' => $e->getMessage()]);
        }

        $sentTo = $channel === 'email' ? self::maskEmail($user->email) : self::maskPhone($user->phone);

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

    /**
     * Request a one-time code to authorize a transaction.
     * Uses Push notification primarily, falls back to SMS/Email based on availability.
     */
    public function requestOtp(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'transaction_type' => 'required|string|in:withdrawal,gold_buy,gold_sell,loan_request,p2p_transfer,scheme_allocation',
            'amount' => 'nullable|numeric',
        ]);

        $type = $validated['transaction_type'];
        $code = (string) random_int(100000, 999999);
        $cacheKey = 'otp_auth_' . $type . '_' . $user->id;

        Cache::put($cacheKey, [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));

        $title = 'Transaction Authorization';
        $message = "Your OTP for {$type} is {$code}. It expires in 10 minutes.";
        if (!empty($validated['amount'])) {
            $message = "Your OTP for {$type} of ₦" . number_format($validated['amount'], 2) . " is {$code}. It expires in 10 minutes.";
        }

        // Determine channel: push is prioritized for transactions as per instructions
        $channel = 'push';
        if (empty($user->fcm_token) && empty($user->device_token)) {
            $channel = 'all'; // fallback to all if no push token
        }

        try {
            $user->notify(new OtpNotification(
                title: $title,
                message: $message,
                channel: $channel,
                context: [
                    'transaction_type' => $type,
                    'amount' => $validated['amount'] ?? null,
                    'expires_in' => 600,
                ]
            ));
        } catch (\Throwable $e) {
            Log::error('OTP notification failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to send OTP. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Authorization code sent successfully.',
            'channel' => $channel,
            'expires_in' => 600,
        ]);
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
