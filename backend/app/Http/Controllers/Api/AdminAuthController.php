<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    // Register a new admin user
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => true,
        ]);

        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    // Admin login with email + password
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->is_admin || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    // Request a password reset link for admin by email
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Ensure the email belongs to an admin account
        $user = User::where('email', $request->email)->where('is_admin', true)->first();
        if (! $user) {
            // Do not reveal that the email doesn't exist or is not admin
            return response()->json(['status' => __(Password::RESET_LINK_SENT)]);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        $sentTo = ['email' => $request->email];

        if ($status === Password::RESET_LINK_SENT) {
            // Send push notification if token available
            if (!empty($user->fcm_token) || !empty($user->device_token)) {
                $sentTo['push'] = 'Device notification sent';
                try {
                    $user->notify(new \App\Notifications\GeneralNotification(
                        title: 'Password Reset Link Sent',
                        message: 'A password reset link has been sent to your email address. Please check your inbox.',
                        data: ['type' => 'security_alert', 'context' => 'admin_forgot_password']
                    ));
                } catch (\Throwable $e) {
                    \Log::warning('Admin forgot password push failed', ['error' => $e->getMessage()]);
                }
            }
            return response()->json([
                'status' => __($status),
                'sent_to' => $sentTo
            ]);
        }

        return response()->json([
            'message' => __($status),
        ], 422);
    }
}
