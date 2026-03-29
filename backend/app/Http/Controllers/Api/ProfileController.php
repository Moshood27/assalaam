<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Return the authenticated member's profile in the shape expected by the mobile app.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        // Build a human-friendly virtual account string if assigned
        $virtualAccount = null;
        if (!empty($user->dva_account_number) && !empty($user->dva_bank_name)) {
            $accName = $user->dva_account_name ?: $user->name;
            $virtualAccount = $user->dva_account_number . ' - ' . $user->dva_bank_name . ' (' . $accName . ')';
        }

        $passportUrl = null;
        if (!empty($user->passport_path)) {
            // Prefer a file that exists in public/{passport_path} (legacy seeder files)
            $publicPath = public_path($user->passport_path);
            if (is_file($publicPath)) {
                // Return a relative path so the frontend dev server can proxy it
                $passportUrl = '/' . ltrim($user->passport_path, '/');
            } else {
                // Fallback to storage (Filament uploads to storage/app/public)
                // Typically returns a relative URL like /storage/<path>
                $passportUrl = Storage::disk('public')->url($user->passport_path);
                // Ensure it is relative
                if (str_starts_with($passportUrl, 'http://') || str_starts_with($passportUrl, 'https://')) {
                    $parsed = parse_url($passportUrl);
                    $passportUrl = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');
                }
            }
        }

        return response()->json([
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'membership_id' => $user->membership_number,
            'branch_id' => $user->branch_id,
            'branch_name' => optional($user->branch)->name,
            'date_joined' => $user->created_at ? $user->created_at->toDateString() : null,
            'virtual_account' => $virtualAccount,
            // Provide human-readable verification details if a DVA exists
            'verification_details' => ($user->dva_bank_name && $user->dva_account_number)
                ? ($user->dva_bank_name . ' - ' . $user->dva_account_number . (
                    $user->dva_account_name ? (' (' . $user->dva_account_name . ')') : ''
                ))
                : null,
            // BVN considered assigned if present, verified timestamp exists, or a DVA has been assigned
            'bvn_assigned' => (bool) ($user->bvn || $user->bvn_verified_at || ($user->dva_account_number && $user->dva_bank_name)),
            'passport_url' => $passportUrl,
        ]);
    }

    /**
     * Upload or replace the authenticated user's passport photo.
     */
    public function uploadPassport(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        $data = $request->validate([
            'passport' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB
        ]);

        $file = $request->file('passport');

        // Ensure upload directory exists under public/upload for direct serving
        $destDir = public_path('upload');
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Create a deterministic-ish filename: user-<id>-<timestamp>.<ext>
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'user-' . $user->id . '-' . time() . '.' . $ext;

        // Move file to public/upload
        $file->move($destDir, $filename);

        // Optionally remove previous public upload if it was in public/upload
        if (!empty($user->passport_path)) {
            $oldPath = public_path($user->passport_path);
            if (str_starts_with($user->passport_path, 'upload/') && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $relativePath = 'upload/' . $filename;
        $user->passport_path = $relativePath;
        $user->save();

        return response()->json([
            'message' => 'Passport uploaded successfully.',
            'passport_url' => '/' . ltrim($relativePath, '/'),
            'passport_path' => $relativePath,
        ]);
    }

    /**
     * Update the authenticated user's email (requires current password).
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['required'],
        ]);

        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $user->email = $data['email'];
        // If your app uses email verification, you may want to reset it here.
        // Commented out to avoid touching schema that may not include this column.
        // $user->email_verified_at = null;
        $user->save();

        return response()->json([
            'message' => 'Email updated successfully.',
            'email' => $user->email,
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        $data = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:6'],
            'confirm_password' => ['required'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            throw ValidationException::withMessages([
                'confirm_password' => ['Password confirmation does not match.'],
            ]);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }

    /**
     * Register or update the authenticated user's device push token.
     */
    public function savePushToken(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'token' => ['required', 'string', 'min:10', 'max:255'],
            'platform' => ['nullable', 'string', 'max:32'],
        ]);

        // Store in both fields for backward compatibility
        $user->device_token = $data['token'];
        if (Schema::hasColumn('users', 'fcm_token')) {
            $user->fcm_token = $data['token'];
        }
        $user->save();

        // Also link this token to the user in fcm_tokens table (multi-device support)
        try {
            if (Schema::hasTable('fcm_tokens')) {
                $existing = DB::table('fcm_tokens')->where('token', $data['token'])->first();
                $now = now();
                $payload = [
                    'user_id' => $user->id,
                    'platform' => $data['platform'] ?? null,
                    'last_seen_at' => $now,
                    'updated_at' => $now,
                ];
                if ($existing) {
                    DB::table('fcm_tokens')->where('id', $existing->id)->update($payload);
                } else {
                    DB::table('fcm_tokens')->insert(array_merge($payload, [
                        'token' => $data['token'],
                        'created_at' => $now,
                    ]));
                }
            }
        } catch (\Throwable $e) {
            // Do not fail the request if optional table is missing or write fails
        }

        return response()->json([
            'message' => 'Push token saved',
            'device_token' => $user->device_token,
            'fcm_token' => $user->fcm_token ?? null,
            'platform' => $data['platform'] ?? null,
        ]);
    }
}
