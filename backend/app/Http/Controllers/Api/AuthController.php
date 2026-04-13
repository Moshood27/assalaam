<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // List of branches for the login dropdown
    public function branches()
    {
        return response()->json(Branch::orderBy('name')->get());
    }

    // Branch-based login with membership number
    public function login(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'membership_number' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('branch_id', $validated['branch_id'])
            ->where(function ($query) use ($validated) {
                $query->where('membership_number', $validated['membership_number'])
                    ->orWhere('phone', $validated['membership_number']);
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'membership_number' => ['The credentials do not match our records for this branch.'],
            ]);
        }

        $token = $user->createToken('mobile_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Member forgot password: send a 6-digit code via email or SMS.
     * Accepts one of: email | phone | (branch_id + membership_number)
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'integer'],
            'membership_number' => ['nullable', 'string'],
        ]);

        // Try resolve user silently (do not reveal existence)
        $user = null;
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        } elseif (!empty($data['phone'])) {
            $user = User::where('phone', $data['phone'])->first();
        } elseif (!empty($data['branch_id']) && !empty($data['membership_number'])) {
            $user = User::where('branch_id', $data['branch_id'])
                ->where(function ($query) use ($data) {
                    $query->where('membership_number', $data['membership_number'])
                        ->orWhere('phone', $data['membership_number']);
                })
                ->first();
        }

        // Always respond with generic message to avoid enumeration
        $generic = ['message' => 'If the account exists, a reset code has been sent.'];

        if (!$user) {
            return response()->json($generic);
        }

        // Determine destination
        $sendEmail = $data['channel'] === 'email' && $user->email;
        $sendSms = $data['channel'] === 'sms' && $user->phone;
        if (!$sendEmail && !$sendSms) {
            // No valid destination; return generic
            return response()->json($generic);
        }

        // Throttle per user: 60s between sends
        $tkey = 'pwd_reset:throttle:'.$user->id;
        if (Cache::has($tkey)) {
            return response()->json(['message' => 'Please wait before requesting another code.', 'retry_after' => 60], 429)
                ->header('Retry-After', 60);
        }

        $code = (string) random_int(100000, 999999);

        $cacheKey = 'pwd_reset:'.$user->id;
        $payload = [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'channel' => $data['channel'],
        ];
        Cache::put($cacheKey, $payload, now()->addMinutes(10));
        Cache::put($tkey, 1, now()->addSeconds(60));

        // Send via selected channel
        try {
            if ($sendEmail) {
                Mail::raw('Your password reset code is '.$code.'. It expires in 10 minutes.', function ($m) use ($user) {
                    $m->to($user->email)->subject('Your Password Reset Code');
                });
                return response()->json([
                    'message' => 'If the account exists, a reset code has been sent.',
                    'sent_to' => ['email' => $this->maskEmail($user->email)],
                    'expires_in' => 600,
                ]);
            }
            if ($sendSms) {
                $sms = app(\App\Services\SmsService::class);
                $sms->send($user->phone, 'Your password reset code is '.$code.'. It expires in 10 minutes.');
                return response()->json([
                    'message' => 'If the account exists, a reset code has been sent.',
                    'sent_to' => ['phone' => $this->maskPhone($user->phone)],
                    'expires_in' => 600,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Password reset code send failed', ['error' => $e->getMessage()]);
        }

        return response()->json($generic);
    }

    /**
     * Member reset password using 6-digit code sent via email or SMS.
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'regex:/^\\d{6}$/'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:6'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'integer'],
            'membership_number' => ['nullable', 'string'],
        ]);

        // Resolve user by provided identifier
        $user = null;
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        } elseif (!empty($data['phone'])) {
            $user = User::where('phone', $data['phone'])->first();
        } elseif (!empty($data['branch_id']) && !empty($data['membership_number'])) {
            $user = User::where('branch_id', $data['branch_id'])
                ->where(function ($query) use ($data) {
                    $query->where('membership_number', $data['membership_number'])
                        ->orWhere('phone', $data['membership_number']);
                })
                ->first();
        }
        if (!$user) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $cacheKey = 'pwd_reset:'.$user->id;
        $payload = Cache::get($cacheKey);
        if (!$payload) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }
        if (!isset($payload['expires_at']) || time() > (int) $payload['expires_at']) {
            Cache::forget($cacheKey);
            return response()->json(['message' => 'Code has expired. Please request a new one.'], 422);
        }
        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            return response()->json(['message' => 'Too many invalid attempts. Please request a new code.'], 429);
        }
        if (!Hash::check($data['code'], (string) $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($cacheKey, $payload, now()->addSeconds(max(60, (int)($payload['expires_at'] - time()))));
            return response()->json(['message' => 'Invalid code.'], 403);
        }

        // Update password and clear token
        $user->password = $data['password']; // hashed by cast
        $user->save();
        Cache::forget($cacheKey);

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $len = strlen($digits);
        if ($len <= 4) return '****';
        return str_repeat('*', max(0, $len - 4)).substr($digits, -4);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) return '***';
        $name = $parts[0];
        $domain = $parts[1];
        $maskedName = strlen($name) <= 2 ? str_repeat('*', strlen($name)) : substr($name, 0, 1).str_repeat('*', strlen($name)-2).substr($name, -1);
        return $maskedName.'@'.$domain;
    }
}
