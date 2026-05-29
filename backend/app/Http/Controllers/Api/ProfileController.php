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
use Illuminate\Support\Facades\Mail;
use App\Mail\NursingMotherAdminNotification;
use App\Models\User;

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


        // Build a human-friendly virtual account string if assigned
        $virtualAccount = null;
        if (!empty($user->dva_account_number) && !empty($user->dva_bank_name)) {
            $accName = $user->dva_account_name ?: $user->name;
            $virtualAccount = $user->dva_account_number . ' - ' . $user->dva_bank_name . ' (' . $accName . ')';
        }

        $passportUrl = null;
        if (!empty($user->passport_path)) {
            $path = ltrim((string) $user->passport_path, '/');
            if (is_file(public_path($path))) {
                $passportUrl = asset($path);
            } else {
                $storagePath = $path;
                if (str_starts_with($storagePath, 'storage/')) {
                    $storagePath = substr($storagePath, 8);
                }
                $passportUrl = Storage::disk('public')->url($storagePath);
            }
        }

        $scoreSvc = app(\App\Services\AssalaamScoreService::class);
        $scoreData = $scoreSvc->scoreForUser($user);
        $scoreEnabled = (bool) \App\Models\Setting::get('loan_credit_score_enabled', config('cooperative.loan_credit_score_enabled', true));

        return response()->json([
            'id' => (int) $user->id,
            'full_name' => $user->full_name,
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
                'provider' => $user->dva_verification_meta['provider'] ?? null,
                'status' => $user->dva_verification_meta['status'] ?? null,
                'score' => $user->dva_verification_meta['score'] ?? null,
            ],
            'is_admin' => $user->isAdmin(),
            'is_staff' => $user->isStaff(),
            'is_board_member' => $user->isBoardMember(),
            'is_committee_member' => $user->isCommitteeMember(),
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
            'assalaam_score' => $scoreData['score'],
            'assalaam_band' => $scoreData['band'],
            'assalaam_breakdown' => $scoreData['breakdown'],
            'assalaam_tips' => $scoreSvc->getScoreTips($user),
            'assalaam_score_enabled' => $scoreEnabled,
            'badges' => $user->badges->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'type' => $b->badge_type,
                'description' => $b->description,
                'earned_at' => $b->earned_at->toDateTimeString(),
            ]),
            'admin_charge_balance' => (float) ($user->admin_charge_balance ?? 0),
            'admin_charge_auto_deduct' => (bool) ($user->admin_charge_auto_deduct ?? true),
            // Membership Enrolment Details
            'surname' => $user->surname,
            'other_names' => $user->other_names,
            'gender' => $user->gender,
            'native_place' => $user->native_place,
            'dob' => $user->dob ? $user->dob->toDateString() : null,
            'marital_status' => $user->marital_status,
            'is_nursing_mother' => (bool) $user->is_nursing_mother,
            'baby_birth_date' => $user->baby_birth_date ? $user->baby_birth_date->toDateString() : null,
            'nursing_mother_status' => $user->nursing_mother_status,
            'nursing_mother_grace_until' => $user->nursing_mother_grace_until ? $user->nursing_mother_grace_until->toDateTimeString() : null,
            'nursing_mother_proof_url' => $user->nursing_mother_proof_path ? Storage::disk('public')->url($user->nursing_mother_proof_path) : null,
            'is_in_nursing_mother_grace' => $user->isInNursingMotherGracePeriod(),
            'occupation' => $user->occupation,
            'secondary_phone' => $user->secondary_phone,
            'residential_address' => $user->residential_address,
            'permanent_address' => $user->permanent_address,
            'nature_of_business' => $user->nature_of_business,
            'business_address' => $user->business_address,
            'has_other_cooperatives' => (bool) ($user->has_other_cooperatives ?? false),
            'other_cooperative_details' => $user->other_cooperative_details,
            'nok_name' => $user->nok_name,
            'nok_address' => $user->nok_address,
            'nok_phone' => $user->nok_phone,
            'nok_relationship' => $user->nok_relationship,
            'guarantor_name' => $user->guarantor_name,
            'guarantor_address' => $user->guarantor_address,
            'guarantor_phone' => $user->guarantor_phone,
            'guarantor_occupation' => $user->guarantor_occupation,
            'religious_society_name' => $user->religious_society_name,
            'imam_name' => $user->imam_name,
            'mosque_address' => $user->mosque_address,
            'imam_phone' => $user->imam_phone,
            'duration_of_jamma_membership' => $user->duration_of_jamma_membership,
            'spouse_father_name' => $user->spouse_father_name,
            'spouse_father_address' => $user->spouse_father_address,
            'spouse_father_business_address' => $user->spouse_father_business_address,
            'spouse_father_phone' => $user->spouse_father_phone,
            'admission_form_number' => $user->admission_form_number,
            'admission_date' => $user->admission_date ? $user->admission_date->toDateString() : null,
            'admission_officer_name' => $user->admission_officer_name,
            'officer_recommendation' => $user->officer_recommendation,
            'approval_status' => $user->approval_status,
        ]);
    }

    /**
     * Upload or replace the authenticated user's passport photo.
     */
    public function uploadPassport(Request $request)
    {
        $user = $request->user();


        $data = $request->validate([
            'passport' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10MB
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

        // Send security alert
        try {
            $user->notify(new \App\Notifications\GeneralNotification(
                title: 'Email Updated',
                message: "Your account email has been updated to {$user->email}. If you did not perform this action, please contact support immediately.",
                data: ['type' => 'security_alert']
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Email update notification failed', ['error' => $e->getMessage()]);
        }

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

        // Send security alert
        try {
            $user->notify(new \App\Notifications\GeneralNotification(
                title: 'Password Updated',
                message: 'Your account password has been successfully updated. If you did not perform this action, please contact support immediately.',
                data: ['type' => 'security_alert']
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Password update notification failed', ['error' => $e->getMessage()]);
        }

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
     * Update the authenticated user's gender.
     */
    public function updateGender(Request $request)
    {
        $request->validate([
            'gender' => 'required|in:male,female',
        ]);

        $user = $request->user();
        $user->gender = $request->gender;
        $user->save();

        return response()->json([
            'message' => 'Gender updated successfully',
            'gender' => $user->gender,
        ]);
    }

    /**
     * Update the authenticated user's notification preferences.
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();


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
     * Apply for nursing mother grace.
     */
    public function applyForNursingMotherGrace(Request $request)
    {
        $user = $request->user();

        if (strtolower($user->gender ?? '') !== 'female') {
            return response()->json(['message' => 'Only female members can apply for nursing mother grace.'], 403);
        }

        $request->validate([
            'proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'baby_birth_date' => 'nullable|date|before_or_equal:today',
        ]);

        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('nursing_mother_proofs', 'public');
            $user->nursing_mother_proof_path = $path;
        }

        $user->nursing_mother_status = 'pending';
        $user->baby_birth_date = $request->baby_birth_date;
        $user->save();

        // Notify admins
        try {
            $adminEmails = User::query()
                ->where('is_admin', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->all();

            if (!empty($adminEmails)) {
                Mail::to($adminEmails)->send(new NursingMotherAdminNotification($user));
            }

            // Also send internal notification to relevant admins
            $user->getAuthorizedAdmins()->each(function ($admin) use ($user) {
                $admin->notifyMember(
                    "Nursing Mother Grace Request",
                    "New nursing mother grace request from {$user->full_name}.",
                    ['type' => 'nursing_mother_grace_request', 'user_id' => $user->id]
                );
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify admins of nursing mother grace request: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Your application for nursing mother grace has been submitted and is pending review.',
            'status' => 'pending'
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
            'message' => 'Opening balances verified successfully. Welcome to AS-SALAAM Pay!',
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

        // Notify relevant admins
        $user->getAuthorizedAdmins()->each(function ($admin) use ($user, $msgBody) {
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
