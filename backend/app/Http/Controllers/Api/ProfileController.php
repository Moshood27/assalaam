<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    /**
     * Get a dynamic list of Nigerian banks from the configured gateway.
     * Query params: gateway=paystack|flutterwave (default: paystack)
     * Response: { banks: [{ code, name }] }
     */
    public function banks(Request $request)
    {
        $gateway = strtolower($request->query('gateway', 'paystack'));

        try {
            if ($gateway === 'flutterwave') {
                $secret = config('services.flutterwave.secret_key');
                if (!$secret) {
                    return response()->json(['message' => 'Payment provider not configured'], 500);
                }
                $resp = Http::withToken($secret)
                    ->acceptJson()
                    ->get('https://api.flutterwave.com/v3/banks/NG');
                if (!$resp->ok() || strtolower((string) $resp->json('status')) !== 'success') {
                    return response()->json([
                        'message' => 'Failed to fetch banks',
                        'errors' => $resp->json('message') ?? 'Unknown error',
                    ], 422);
                }
                $banks = collect($resp->json('data') ?? [])
                    ->filter(fn ($b) => !empty($b['code']) && !empty($b['name']))
                    ->map(fn ($b) => [
                        'code' => (string) $b['code'],
                        'name' => (string) $b['name'],
                    ])
                    ->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)
                    ->values()
                    ->all();
                return response()->json(['banks' => $banks]);
            }

            // Default: Paystack
            $secret = config('services.paystack.secret_key');
            // Even if secret is missing, Paystack bank list endpoint may be public, but we keep behavior consistent
            $req = Http::acceptJson();
            if ($secret) {
                $req = $req->withToken($secret);
            }
            $resp = $req->get('https://api.paystack.co/bank', [
                'country' => 'nigeria',
                'currency' => 'NGN',
                'type' => 'nuban',
            ]);
            if (!$resp->ok() || !($resp->json('status') === true)) {
                return response()->json([
                    'message' => 'Failed to fetch banks',
                    'errors' => $resp->json('message') ?? 'Unknown error',
                ], 422);
            }
            $banks = collect($resp->json('data') ?? [])
                ->filter(fn ($b) => !empty($b['code']) && !empty($b['name']))
                ->map(fn ($b) => [
                    'code' => (string) $b['code'],
                    'name' => (string) $b['name'],
                ])
                ->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)
                ->values()
                ->all();

            return response()->json(['banks' => $banks]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unable to fetch banks at this time.'], 500);
        }
    }
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

        $scoreSvc = app(\App\Services\AttaqwaScoreService::class);
        $scoreData = $scoreSvc->scoreForUser($user);

        return response()->json([
            'id' => (int) $user->id,
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
            // KYC 2.0 status: expose verification flags and provider details (if present)
            'bvn_verified' => (bool) $user->bvn_verified_at,
            'bvn_verified_at' => $user->bvn_verified_at ? $user->bvn_verified_at->toDateTimeString() : null,
            'kyc' => [
                'provider' => is_array($user->dva_verification_meta ?? null) ? ($user->dva_verification_meta['provider'] ?? null) : (is_object($user->dva_verification_meta ?? null) ? ($user->dva_verification_meta->provider ?? null) : null),
                'status' => is_array($user->dva_verification_meta ?? null) ? ($user->dva_verification_meta['status'] ?? null) : (is_object($user->dva_verification_meta ?? null) ? ($user->dva_verification_meta->status ?? null) : null),
                'score' => is_array($user->dva_verification_meta ?? null) ? ($user->dva_verification_meta['score'] ?? null) : (is_object($user->dva_verification_meta ?? null) ? ($user->dva_verification_meta->score ?? null) : null),
            ],
            'passport_url' => $passportUrl,
            // Transaction PIN status for improved UX on the client
            'pin_set' => method_exists($user, 'hasTransactionPin') ? $user->hasTransactionPin() : (!empty($user->transaction_pin_hash)),
            'pin_set_at' => $user->pin_set_at ? $user->pin_set_at->toDateTimeString() : null,
            // Notification preferences
            'notify_email' => (bool) ($user->notify_email ?? true),
            'notify_sms' => (bool) ($user->notify_sms ?? true),
            'notify_push' => (bool) ($user->notify_push ?? true),
            // Member's verified cash-out bank details (if saved)
            'bank_details' => [
                'bank_code' => $user->bank_code,
                'bank_name' => $user->bank_name,
                'account_number' => $user->account_number,
                'account_name' => $user->account_name,
                'has_verified' => (bool) ($user->bank_code && $user->account_number && $user->account_name),
            ],
            // Member's vendor profile (if exists)
            'vendor' => $user->vendor ? [
                'id' => (int) $user->vendor->id,
                'name' => $user->vendor->name, // Model uses 'name' for business name
                'is_approved' => (bool) $user->vendor->is_approved,
                'is_active' => (bool) $user->vendor->is_active,
                'commission_rate' => (float) $user->vendor->commission_rate,
            ] : null,
            'attaqwa_score' => $scoreData['score'],
            'attaqwa_band' => $scoreData['band'],
            'attaqwa_breakdown' => $scoreData['breakdown'],
            'attaqwa_tips' => $scoreSvc->getScoreTips($user),
            'badges' => $user->badges->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'type' => $b->badge_type,
                'description' => $b->description,
                'earned_at' => $b->earned_at->toDateTimeString(),
            ]),
            'admin_charge_balance' => (float) ($user->admin_charge_balance ?? 0),
            'admin_charge_auto_deduct' => (bool) ($user->admin_charge_auto_deduct ?? true),
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

    /**
     * Resolve and (optionally) save verified bank details for the authenticated user.
     * Workflow:
     *  - Client posts bank_code, account_number, optional bank_name and gateway.
     *  - We call provider's Resolve Account API to fetch the registered account name.
     *  - If confirm=false/missing: return the resolved name for user confirmation (do not save).
     *  - If confirm=true: persist bank_name, bank_code, account_number, account_name on the user.
     */
    public function saveBankDetails(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['required', 'regex:/^\d{10}$/'],
            'gateway' => ['nullable', 'in:paystack,flutterwave'],
            'confirm' => ['nullable', 'boolean'],
        ]);

        $gateway = strtolower($data['gateway'] ?? 'paystack');
        $bankCode = trim($data['bank_code']);
        $bankNameInput = trim((string) ($data['bank_name'] ?? '')) ?: null;
        $accountNumber = preg_replace('/[^0-9]/', '', $data['account_number']);

        $resolvedName = null;
        $provider = null;

        if ($gateway === 'flutterwave') {
            $secret = config('services.flutterwave.secret_key');
            if (!$secret) {
                return response()->json(['message' => 'Payment provider not configured'], 500);
            }
            $provider = 'flutterwave';
            $resp = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.flutterwave.com/v3/accounts/resolve', [
                    'account_number' => $accountNumber,
                    'account_bank' => $bankCode,
                ]);
            if (!$resp->ok() || (strtolower((string) $resp->json('status')) !== 'success')) {
                return response()->json([
                    'message' => 'Failed to resolve bank account',
                    'errors' => $resp->json('message') ?? 'Unknown error',
                ], 422);
            }
            $resolvedName = trim((string) ($resp->json('data.account_name') ?? '')) ?: null;
        } else { // paystack (default)
            $secret = config('services.paystack.secret_key');
            if (!$secret) {
                return response()->json(['message' => 'Payment provider not configured'], 500);
            }
            $provider = 'paystack';
            $resp = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.paystack.co/bank/resolve', [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ]);
            if (!$resp->ok() || !($resp->json('status') === true)) {
                return response()->json([
                    'message' => 'Failed to resolve bank account',
                    'errors' => $resp->json('message') ?? 'Unknown error',
                ], 422);
            }
            $resolvedName = trim((string) ($resp->json('data.account_name') ?? '')) ?: null;
        }

        if (!$resolvedName) {
            return response()->json([
                'message' => 'Could not determine account name from provider response.'], 422);
        }

        $confirm = (bool) ($data['confirm'] ?? false);
        if (!$confirm) {
            return response()->json([
                'resolved_name' => $resolvedName,
                'bank_code' => $bankCode,
                'bank_name' => $bankNameInput,
                'account_number' => $accountNumber,
                'provider' => $provider,
                'has_verified' => false,
                'message' => 'Confirm to save these bank details.',
            ]);
        }

        // Save verified details on user
        $user->bank_code = $bankCode;
        $user->bank_name = $bankNameInput; // may be null; UI can show just code if name unknown
        $user->account_number = $accountNumber;
        $user->account_name = $resolvedName;
        $user->save();

        return response()->json([
            'message' => 'Bank details saved successfully.',
            'bank_details' => [
                'bank_code' => $user->bank_code,
                'bank_name' => $user->bank_name,
                'account_number' => $user->account_number,
                'account_name' => $user->account_name,
                'has_verified' => true,
            ],
        ]);
    }

    /**
     * Update the authenticated user's notification preferences.
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        $data = $request->validate([
            'notify_email' => ['required', 'boolean'],
            'notify_sms' => ['required', 'boolean'],
            'notify_push' => ['required', 'boolean'],
        ]);

        $user->notify_email = $data['notify_email'];
        $user->notify_sms = $data['notify_sms'];
        $user->notify_push = $data['notify_push'];
        $user->save();

        return response()->json([
            'message' => 'Notification preferences updated successfully.',
            'preferences' => [
                'notify_email' => (bool) $user->notify_email,
                'notify_sms' => (bool) $user->notify_sms,
                'notify_push' => (bool) $user->notify_push,
            ],
        ]);
    }

    /**
     * Update the authenticated user's administrative charge auto-deduction preference.
     */
    public function updateAdminChargePreference(Request $request)
    {
        $user = $request->user();

        // Restrict this endpoint to non-admin members only
        if (method_exists($user, 'getAttribute') && (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use /api/admin/profile endpoints.'], 403);
        }

        $validated = $request->validate([
            'admin_charge_auto_deduct' => 'required|boolean',
        ]);

        $user->admin_charge_auto_deduct = $validated['admin_charge_auto_deduct'];
        $user->save();

        return response()->json([
            'message' => 'Administrative charge preference updated successfully.',
            'admin_charge_auto_deduct' => (bool) $user->admin_charge_auto_deduct,
        ]);
    }

    /**
     * Verify the migrated opening balances.
     */
    public function verifyMigration(Request $request)
    {
        $user = $request->user();

        if (!$user->migrated_at) {
            return response()->json(['message' => 'This account was not part of the system migration.'], 400);
        }

        if ($user->verified_at) {
            return response()->json(['message' => 'Account already verified.'], 400);
        }

        $user->verified_at = now();
        $user->save();

        return response()->json([
            'message' => 'Opening balances verified successfully. Welcome to Attaqwa Pay!',
            'verified_at' => $user->verified_at,
        ]);
    }

    /**
     * Report a discrepancy in the migrated opening balances.
     */
    public function reportMigrationError(Request $request)
    {
        $user = $request->user();

        if (!$user->migrated_at) {
            return response()->json(['message' => 'This account was not part of the system migration.'], 400);
        }

        if ($user->verified_at) {
            return response()->json(['message' => 'Account already verified.'], 400);
        }

        $validated = $request->validate([
            'details' => 'required|string|max:1000',
        ]);

        // Create a support message for the admin to review
        $msgBody = "MIGRATION DISCREPANCY REPORT\n\nUser: {$user->name} ({$user->membership_number})\nDetails: " . $validated['details'];

        $msg = \App\Models\SupportMessage::create([
            'user_id' => $user->id,
            'sender_type' => 'member',
            'sender_id' => $user->id,
            'body' => $msgBody,
        ]);

        $user->discrepancy_reported_at = now();
        $user->save();

        // Notify admins
        \App\Models\User::where('is_admin', true)->each(function ($admin) use ($user, $msgBody) {
            $admin->notifyMember(
                "Migration Discrepancy",
                "New report from {$user->name} regarding their opening balance.",
                ['type' => 'migration_discrepancy', 'user_id' => $user->id]
            );
        });

        return response()->json([
            'message' => 'Your report has been submitted to the admin for review. We will contact you shortly.',
        ]);
    }
}
