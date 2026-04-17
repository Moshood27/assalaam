<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\Setting;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // List of branches for the login dropdown
    public function status()
    {
        return response()->json([
            'status' => 'ok',
            'mobile_min_version' => Setting::get('mobile_min_version', config('cooperative.mobile_min_version')),
            'mobile_current_version' => Setting::get('mobile_current_version', config('cooperative.mobile_current_version')),
            'maintenance_mode' => (bool) Setting::get('maintenance_mode', config('cooperative.maintenance_mode')),
            'maintenance_message' => Setting::get('maintenance_message', config('cooperative.maintenance_message')),
            'maintenance_until' => Setting::get('maintenance_until', config('cooperative.maintenance_until')),
            'system_announcement' => Setting::get('system_announcement', config('cooperative.system_announcement')),
            'play_store_url' => Setting::get('play_store_url', config('cooperative.play_store_url')),
            'app_name' => config('app.name'),
        ]);
    }

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
            'remember' => 'nullable|boolean',
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

        if ($user->approval_status !== 'approved') {
            $status = ucfirst($user->approval_status ?: 'pending');
            throw ValidationException::withMessages([
                'membership_number' => ["Your account is {$status}. Please contact the administrator for approval."],
            ]);
        }

        $tokenName = $request->boolean('remember') ? 'remember_token' : 'mobile_token';
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Member forgot password: send a 6-digit code via email or SMS.
     * Supports both registered Users and Applicants (MemberApplications).
     * Accepts one of: email | phone | (branch_id + membership_number)
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms,push'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'integer'],
            'membership_number' => ['nullable', 'string'],
        ]);

        // Try resolve user silently (do not reveal existence)
        $user = null;
        $isApplicant = false;

        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                $user = \App\Models\MemberApplication::where('email', $data['email'])->whereNull('finalized_at')->first();
                if ($user) $isApplicant = true;
            }
        } elseif (!empty($data['phone'])) {
            $user = User::where('phone', $data['phone'])->first();
            if (!$user) {
                $user = \App\Models\MemberApplication::where('phone', $data['phone'])->whereNull('finalized_at')->first();
                if ($user) $isApplicant = true;
            }
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
        $hasPush = !empty($user->fcm_token) || !empty($user->device_token);
        $sendEmail = $data['channel'] === 'email' && $user->email;
        $sendSms = $data['channel'] === 'sms' && $user->phone;
        $sendPushExplicit = $data['channel'] === 'push' && $hasPush;

        if (!$sendEmail && !$sendSms && !$sendPushExplicit) {
            // If they chose push but don't have it, or email/sms but don't have it
            return response()->json($generic);
        }

        // Throttle per target: 60s between sends
        $throttleId = ($isApplicant ? 'app_' : 'user_') . $user->id;
        $tkey = 'pwd_reset:throttle:' . $throttleId;
        if (Cache::has($tkey)) {
            return response()->json(['message' => 'Please wait before requesting another code.', 'retry_after' => 60], 429)
                ->header('Retry-After', 60);
        }

        $code = (string) random_int(100000, 999999);

        $cacheKey = 'pwd_reset:' . $throttleId;
        $payload = [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'channel' => $data['channel'],
            'is_applicant' => $isApplicant,
        ];
        Cache::put($cacheKey, $payload, now()->addMinutes(10));
        Cache::put($tkey, 1, now()->addSeconds(60));

        // Determine channel(s)
        // Always send push if available, plus the requested channel
        $notificationChannel = $data['channel'];
        if ($hasPush && $data['channel'] !== 'push') {
            $notificationChannel .= ',push';
        }

        try {
            $user->notify(new OtpNotification(
                title: 'Password Reset Code',
                message: "Your password reset code is {$code}. It expires in 10 minutes.",
                channel: $notificationChannel,
                context: ['type' => 'password_reset']
            ));

            $sentTo = [];
            if ($sendEmail) $sentTo['email'] = $this->maskEmail($user->email);
            if ($sendSms) $sentTo['phone'] = $this->maskPhone($user->phone);
            if ($hasPush) $sentTo['push'] = 'Device notification sent';

            return response()->json([
                'message' => 'If the account exists, a reset code has been sent.',
                'sent_to' => $sentTo,
                'expires_in' => 600,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Password reset notification failed', ['error' => $e->getMessage()]);
        }

        return response()->json($generic);
    }

    /**
     * Member reset password using 6-digit code sent via email or SMS.
     * Supports both registered Users and Applicants (MemberApplications).
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
        $isApplicant = false;
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                $user = \App\Models\MemberApplication::where('email', $data['email'])->whereNull('finalized_at')->first();
                if ($user) $isApplicant = true;
            }
        } elseif (!empty($data['phone'])) {
            $user = User::where('phone', $data['phone'])->first();
            if (!$user) {
                $user = \App\Models\MemberApplication::where('phone', $data['phone'])->whereNull('finalized_at')->first();
                if ($user) $isApplicant = true;
            }
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

        $throttleId = ($isApplicant ? 'app_' : 'user_') . $user->id;
        $cacheKey = 'pwd_reset:' . $throttleId;
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
        if ($isApplicant) {
            $user->password_hash = \Illuminate\Support\Facades\Crypt::encryptString($data['password']);
        } else {
            $user->password = $data['password']; // hashed by cast
        }
        $user->save();
        Cache::forget($cacheKey);

        $response = ['message' => 'Password has been reset successfully.'];
        if ($isApplicant) {
            $response['token'] = $user->token;
        } else {
            $response['membership_number'] = $user->membership_number;
        }

        // Send security alert
        try {
            $user->notify(new \App\Notifications\GeneralNotification(
                title: 'Password Changed',
                message: 'Your account password has been successfully reset. If you did not perform this action, please contact support immediately.',
                data: ['type' => 'security_alert']
            ));
        } catch (\Throwable $e) {
            Log::warning('Password reset confirmation notification failed', ['error' => $e->getMessage()]);
        }

        return response()->json($response);
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
