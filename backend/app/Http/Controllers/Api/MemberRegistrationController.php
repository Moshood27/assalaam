<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberRegistrationController extends Controller
{
    /**
     * Start a new member application and return a token to continue the process.
     */
    public function start(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:male,female'],
            'native_place' => ['nullable', 'string', 'max:255'],
            'dob' => ['required', 'date'],
            'marital_status' => ['required', 'string', 'in:single,married,divorced,widow'],
            'occupation' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'secondary_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'residential_address' => ['required', 'string', 'max:1000'],
            'permanent_address' => ['required', 'string', 'max:1000'],
            'branch_id' => ['required', 'exists:branches,id'],
            'password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:password'],
            'fcm_token' => ['nullable', 'string'],
            'device_token' => ['nullable', 'string'],

            // Business & Professional Information
            'nature_of_business' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:1000'],
            'has_other_cooperatives' => ['nullable', 'boolean'],
            'other_cooperative_details' => ['nullable', 'string', 'max:1000'],

            // Next of Kin
            'nok_name' => ['nullable', 'string', 'max:255'],
            'nok_address' => ['nullable', 'string', 'max:1000'],
            'nok_phone' => ['nullable', 'string', 'max:30'],
            'nok_relationship' => ['nullable', 'string', 'max:100'],

            // Guarantor Details
            'guarantor_name' => ['nullable', 'string', 'max:255'],
            'guarantor_address' => ['nullable', 'string', 'max:1000'],
            'guarantor_phone' => ['nullable', 'string', 'max:30'],
            'guarantor_occupation' => ['nullable', 'string', 'max:255'],

            // Religious Information & Imam's Attestation
            'religious_society_name' => ['nullable', 'string', 'max:255'],
            'imam_name' => ['nullable', 'string', 'max:255'],
            'mosque_address' => ['nullable', 'string', 'max:1000'],
            'imam_phone' => ['nullable', 'string', 'max:30'],
            'duration_of_jamma_membership' => ['nullable', 'string', 'max:100'],

            // Information for Female Members
            'spouse_father_name' => ['nullable', 'string', 'max:255'],
            'spouse_father_address' => ['nullable', 'string', 'max:1000'],
            'spouse_father_business_address' => ['nullable', 'string', 'max:1000'],
            'spouse_father_phone' => ['nullable', 'string', 'max:30'],
        ]);

        // If there's an existing non-finalized application for this email, reuse it
        $existing = MemberApplication::where('email', $data['email'] ?? null)
            ->whereNull('finalized_at')
            ->latest('id')
            ->first();

        if ($existing) {
            // Update details to latest submission
            $existing->fill(array_merge($data, [
                'fcm_token' => $data['fcm_token'] ?? $existing->fcm_token,
                'device_token' => $data['device_token'] ?? $existing->device_token,
                'password_hash' => Crypt::encryptString($data['password']), // store encrypted; will hash once on User model
                'submitted_at' => now(),
            ]));
            $existing->save();

            return response()->json([
                'message' => 'Existing application found. Please continue.',
                'token' => $existing->token,
            ]);
        }

        // Create a short token the frontend will keep to continue the process
        $token = Str::uuid()->toString();

        $app = MemberApplication::create(array_merge($data, [
            'token' => $token,
            'fcm_token' => $data['fcm_token'] ?? null,
            'device_token' => $data['device_token'] ?? null,
            'password_hash' => Crypt::encryptString($data['password']), // store encrypted; will hash once on User model
            'submitted_at' => now(),
        ]));

        return response()->json([
            'message' => 'Application started. Please upload required documents and complete verification.',
            'token' => $app->token,
        ]);
    }

    /**
     * Upload required documents for an application.
     */
    public function upload(Request $request)
    {
        $rules = [
            'token' => ['required', 'string', Rule::exists('member_applications', 'token')],
            'passport' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10MB
            'id_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'], // 15MB
            'proof_of_address' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'], // 15MB
            'guarantor_signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'guarantor_signature_base64' => ['nullable', 'string'],
            'imam_signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'imam_signature_base64' => ['nullable', 'string'],
            'spouse_father_consent_signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'spouse_father_consent_signature_base64' => ['nullable', 'string'],
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::error('Member application upload validation failed', [
                'token' => $request->input('token'),
                'errors' => $validator->errors()->toArray(),
                'request_keys' => array_keys($request->all()),
                'file_keys' => array_keys($request->allFiles()),
            ]);
            $validator->validate();
        }

        $app = MemberApplication::where('token', $request->input('token'))->firstOrFail();

        $baseDir = public_path('upload/apps/'.$app->token);
        if (!is_dir($baseDir)) @mkdir($baseDir, 0755, true);

        $updated = [];

        if ($file = $request->file('passport')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $name = 'passport-'.time().'.'.$ext;
            $file->move($baseDir, $name);
            $app->passport_path = 'upload/apps/'.$app->token.'/'.$name;
            $updated['passport_path'] = $app->passport_path;
        }
        if ($file = $request->file('id_card')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $name = 'idcard-'.time().'.'.$ext;
            $file->move($baseDir, $name);
            $app->id_card_path = 'upload/apps/'.$app->token.'/'.$name;
            $updated['id_card_path'] = $app->id_card_path;
        }
        if ($file = $request->file('proof_of_address')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $name = 'poa-'.time().'.'.$ext;
            $file->move($baseDir, $name);
            $app->proof_of_address_path = 'upload/apps/'.$app->token.'/'.$name;
            $updated['proof_of_address_path'] = $app->proof_of_address_path;
        }
        if ($base64 = $request->input('guarantor_signature_base64')) {
            $data = explode(',', $base64);
            if (count($data) > 1) {
                $content = base64_decode($data[1]);
                $name = 'guarantor-sig-'.time().'.png';
                file_put_contents($baseDir.'/'.$name, $content);
                $app->guarantor_signature_path = 'upload/apps/'.$app->token.'/'.$name;
                $updated['guarantor_signature_path'] = $app->guarantor_signature_path;
            }
        } elseif ($file = $request->file('guarantor_signature')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $name = 'guarantor-sig-'.time().'.'.$ext;
            $file->move($baseDir, $name);
            $app->guarantor_signature_path = 'upload/apps/'.$app->token.'/'.$name;
            $updated['guarantor_signature_path'] = $app->guarantor_signature_path;
        }

        if ($base64 = $request->input('imam_signature_base64')) {
            $data = explode(',', $base64);
            if (count($data) > 1) {
                $content = base64_decode($data[1]);
                $name = 'imam-sig-'.time().'.png';
                file_put_contents($baseDir.'/'.$name, $content);
                $app->imam_signature_path = 'upload/apps/'.$app->token.'/'.$name;
                $updated['imam_signature_path'] = $app->imam_signature_path;
            }
        } elseif ($file = $request->file('imam_signature')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $name = 'imam-sig-'.time().'.'.$ext;
            $file->move($baseDir, $name);
            $app->imam_signature_path = 'upload/apps/'.$app->token.'/'.$name;
            $updated['imam_signature_path'] = $app->imam_signature_path;
        }

        if ($base64 = $request->input('spouse_father_consent_signature_base64')) {
            $data = explode(',', $base64);
            if (count($data) > 1) {
                $content = base64_decode($data[1]);
                $name = 'spouse-father-sig-'.time().'.png';
                file_put_contents($baseDir.'/'.$name, $content);
                $app->spouse_father_consent_signature_path = 'upload/apps/'.$app->token.'/'.$name;
                $updated['spouse_father_consent_signature_path'] = $app->spouse_father_consent_signature_path;
            }
        } elseif ($file = $request->file('spouse_father_consent_signature')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $name = 'spouse-father-sig-'.time().'.'.$ext;
            $file->move($baseDir, $name);
            $app->spouse_father_consent_signature_path = 'upload/apps/'.$app->token.'/'.$name;
            $updated['spouse_father_consent_signature_path'] = $app->spouse_father_consent_signature_path;
        }

        $app->save();

        return response()->json([
            'message' => 'Documents uploaded.',
            'application' => [
                'passport_path' => $app->passport_path,
                'id_card_path' => $app->id_card_path,
                'proof_of_address_path' => $app->proof_of_address_path,
                'guarantor_signature_path' => $app->guarantor_signature_path,
                'imam_signature_path' => $app->imam_signature_path,
                'spouse_father_consent_signature_path' => $app->spouse_father_consent_signature_path,
            ],
        ]);
    }

    /**
     * Send OTPs to email and SMS for verification.
     */
    public function sendOtps(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token' => ['required', 'string', Rule::exists('member_applications', 'token')],
        ]);

        if ($validator->fails()) {
            Log::warning('Send OTPs validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);
            $validator->validate();
        }

        $app = MemberApplication::where('token', $request->input('token'))->firstOrFail();

        // Basic backoff: allow at most once per 30 seconds
        if ($app->last_otp_sent_at && $app->last_otp_sent_at->diffInSeconds(now()) < 30) {
            return response()->json(['message' => 'Please wait at least 30 seconds before requesting new codes.'], 429);
        }

        $code = (string) random_int(100000, 999999);
        $app->email_otp_hash = $app->email ? Hash::make($code) : null;
        $app->sms_otp_hash = $app->phone ? Hash::make($code) : null;
        $app->otp_expires_at = now()->addMinutes(10);
        $app->email_otp_attempts = 0;
        $app->sms_otp_attempts = 0;
        $app->last_otp_sent_at = now();
        $app->save();

        Log::info('Registration OTP generated', [
            'token' => $app->token,
            'email_present' => !empty($app->email),
            'phone_present' => !empty($app->phone),
            'expires_at' => $app->otp_expires_at->toDateTimeString(),
        ]);

        $sentTo = [];

        // Determine if push is available
        $hasPush = !empty($app->fcm_token) || !empty($app->device_token);

        try {
            $app->notify(new OtpNotification(
                title: 'Registration Verification Code',
                message: "Your verification code is {$code}. It expires in 10 minutes. Use this code for both email and phone.",
                channel: 'all', // notification handles via()
                context: ['type' => 'registration']
            ));

            if ($app->email) $sentTo['email'] = $this->maskEmail($app->email);
            if ($app->phone) $sentTo['phone'] = $this->maskPhone($app->phone);
            if ($hasPush) $sentTo['push'] = 'Device notification sent';

        } catch (\Throwable $e) {
            Log::warning('Registration OTP notifications partially failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Verification codes sent if contact information is valid.',
            'sent_to' => $sentTo,
            'expires_in' => 600,
        ]);
    }

    /** Verify email code */
    public function verifyEmail(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token' => ['required', 'string', Rule::exists('member_applications', 'token')],
            'code' => ['required', 'regex:/^\\d{6}$/'],
        ]);

        if ($validator->fails()) {
            Log::warning('Email verification validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);
            $validator->validate();
        }

        $data = $validator->validated();
        $app = MemberApplication::where('token', $data['token'])->firstOrFail();

        if (!$app->email_otp_hash || !$app->otp_expires_at || now()->greaterThan($app->otp_expires_at)) {
            Log::warning('Email verification failed: Code expired or not requested', [
                'token' => $data['token'],
                'has_hash' => !empty($app->email_otp_hash),
                'expires_at' => $app->otp_expires_at?->toDateTimeString(),
                'now' => now()->toDateTimeString(),
            ]);
            return response()->json(['message' => 'Code expired or not requested.'], 422);
        }
        $attempts = (int) $app->email_otp_attempts;
        if ($attempts >= 5) {
            Log::warning('Email verification failed: Too many attempts', ['token' => $data['token']]);
            return response()->json(['message' => 'Too many invalid attempts. Please resend a new code.'], 429);
        }
        if (!Hash::check($data['code'], $app->email_otp_hash)) {
            $app->email_otp_attempts = $attempts + 1;
            $app->save();
            Log::warning('Email verification failed: Invalid code', [
                'token' => $data['token'],
                'attempts' => $app->email_otp_attempts,
            ]);
            return response()->json(['message' => 'Invalid code.'], 403);
        }

        Log::info('Email verified successfully', ['token' => $data['token']]);
        $app->email_verified_at = now();
        $app->email_otp_hash = null;
        $app->save();

        return response()->json(['message' => 'Email verified.']);
    }

    /** Verify SMS code */
    public function verifySms(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token' => ['required', 'string', Rule::exists('member_applications', 'token')],
            'code' => ['required', 'regex:/^\\d{6}$/'],
        ]);

        if ($validator->fails()) {
            Log::warning('SMS verification validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);
            $validator->validate();
        }

        $data = $validator->validated();
        $app = MemberApplication::where('token', $data['token'])->firstOrFail();

        if (!$app->sms_otp_hash || !$app->otp_expires_at || now()->greaterThan($app->otp_expires_at)) {
            Log::warning('SMS verification failed: Code expired or not requested', [
                'token' => $data['token'],
                'has_hash' => !empty($app->sms_otp_hash),
                'expires_at' => $app->otp_expires_at?->toDateTimeString(),
                'now' => now()->toDateTimeString(),
            ]);
            return response()->json(['message' => 'Code expired or not requested.'], 422);
        }
        $attempts = (int) $app->sms_otp_attempts;
        if ($attempts >= 5) {
            Log::warning('SMS verification failed: Too many attempts', ['token' => $data['token']]);
            return response()->json(['message' => 'Too many invalid attempts. Please resend a new code.'], 429);
        }
        if (!Hash::check($data['code'], $app->sms_otp_hash)) {
            $app->sms_otp_attempts = $attempts + 1;
            $app->save();
            Log::warning('SMS verification failed: Invalid code', [
                'token' => $data['token'],
                'attempts' => $app->sms_otp_attempts,
                'input_length' => strlen($data['code']),
            ]);
            return response()->json(['message' => 'Invalid code.'], 403);
        }

        Log::info('SMS verified successfully', ['token' => $data['token']]);
        $app->phone_verified_at = now();
        $app->sms_otp_hash = null;
        $app->save();

        return response()->json(['message' => 'Phone verified.']);
    }

    /** Finalize the application, create a User and assign a membership number */
    public function finalize(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token' => ['required', 'string', Rule::exists('member_applications', 'token')],
            'bvn' => ['required', 'regex:/^\\d{11}$/'],
        ]);

        if ($validator->fails()) {
            Log::warning('Registration finalization validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);
            $validator->validate();
        }

        $data = $validator->validated();
        $app = MemberApplication::where('token', $request->input('token'))->firstOrFail();

        // Prevent re-finalizing the same application
        if (!empty($app->finalized_at)) {
            return response()->json(['message' => 'This application has already been finalized. Please proceed to login.'], 422);
        }
        // Prevent duplicate BVN usage across different accounts
        $bvn = $data['bvn'];
        if (User::where('bvn', $bvn)->exists()) {
            return response()->json(['message' => 'This BVN is already associated with an existing member. If you believe this is an error, please contact support.'], 422);
        }

        // Validate required documents and verifications
        $missing = [];
        foreach ([
            'passport_path' => 'Passport photo',
            'id_card_path' => 'Valid ID card',
            'proof_of_address_path' => 'Proof of address',
        ] as $field => $label) {
            if (empty($app->{$field})) $missing[] = $label;
        }
        if (!empty($missing)) {
            return response()->json(['message' => 'Missing required documents: '.implode(', ', $missing)], 422);
        }
        if (empty($app->email_verified_at) || empty($app->phone_verified_at)) {
            return response()->json(['message' => 'Both email and phone must be verified before joining.'], 422);
        }

        // Perform BVN + Face match verification using configured KYC provider
        try {
            $verifier = app(\App\Services\Kyc\KycVerifier::class);
            $kyc = $verifier->verifyBvnWithFace($bvn, $app->passport_path, $app->id_card_path);
        } catch (\Throwable $e) {
            Log::error('KYC verification exception', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to perform KYC verification. Please try again later.'], 503);
        }
        if (empty($kyc['success'])) {
            $reason = $kyc['status'] ?? 'failed';
            Log::warning('KYC verification failed', [
                'provider' => $kyc['provider'] ?? null,
                'status' => $kyc['status'] ?? null,
                'score' => $kyc['score'] ?? null,
                'reference_source' => $kyc['meta']['reference_source'] ?? null,
                'app_token' => $app->token,
                'email' => $app->email,
                'branch_id' => $app->branch_id,
            ]);
            return response()->json([
                'message' => 'KYC verification failed: '.$reason,
                'details' => $kyc['meta'] ?? null,
            ], 422);
        }

        // KYC passed — log minimal observability fields for support
        Log::info('KYC verification passed', [
            'provider' => $kyc['provider'] ?? null,
            'status' => $kyc['status'] ?? null,
            'score' => $kyc['score'] ?? null,
            'reference_source' => $kyc['meta']['reference_source'] ?? null,
            'app_token' => $app->token,
            'email' => $app->email,
            'branch_id' => $app->branch_id,
        ]);

        // Generate a unique membership number within the branch (6 digits)
        $membership = User::generateMembershipNumber((int) $app->branch_id);

        // Create the user
        $user = new User();
        $user->name = $app->name;
        $user->surname = $app->surname;
        $user->other_names = $app->other_names;
        $user->gender = $app->gender;
        $user->native_place = $app->native_place;
        $user->dob = $app->dob;
        $user->marital_status = $app->marital_status;
        $user->occupation = $app->occupation;
        $user->email = $app->email;
        $user->phone = $app->phone;
        $user->secondary_phone = $app->secondary_phone;
        $user->address = $app->address;
        $user->residential_address = $app->residential_address;
        $user->permanent_address = $app->permanent_address;
        $user->branch_id = $app->branch_id;
        $user->membership_number = $membership;

        // Business & Kin
        $user->nature_of_business = $app->nature_of_business;
        $user->business_address = $app->business_address;
        $user->has_other_cooperatives = $app->has_other_cooperatives;
        $user->other_cooperative_details = $app->other_cooperative_details;
        $user->nok_name = $app->nok_name;
        $user->nok_address = $app->nok_address;
        $user->nok_phone = $app->nok_phone;
        $user->nok_relationship = $app->nok_relationship;

        // Guarantor
        $user->guarantor_name = $app->guarantor_name;
        $user->guarantor_address = $app->guarantor_address;
        $user->guarantor_phone = $app->guarantor_phone;
        $user->guarantor_occupation = $app->guarantor_occupation;
        $user->guarantor_signature_path = $app->guarantor_signature_path;

        // Religious & Imam
        $user->religious_society_name = $app->religious_society_name;
        $user->imam_name = $app->imam_name;
        $user->mosque_address = $app->mosque_address;
        $user->imam_phone = $app->imam_phone;
        $user->duration_of_jamma_membership = $app->duration_of_jamma_membership;
        $user->imam_approval_status = $app->imam_approval_status;
        $user->imam_approved_at = $app->imam_approved_at;
        $user->imam_signature_path = $app->imam_signature_path;

        // Female / Wali
        $user->spouse_father_name = $app->spouse_father_name;
        $user->spouse_father_address = $app->spouse_father_address;
        $user->spouse_father_business_address = $app->spouse_father_business_address;
        $user->spouse_father_phone = $app->spouse_father_phone;
        $user->spouse_father_consent_signature_path = $app->spouse_father_consent_signature_path;

        // Official Use
        $user->approval_status = 'pending'; // Changed from 'approved' to 'pending' to require admin approval
        $user->admission_date = now();
        $user->admission_officer_name = 'System/KYC';

        // Decrypt the stored application password and let the User model hash it once
        try {
            $plain = Crypt::decryptString($app->password_hash);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not finalize: invalid stored password. Please restart registration.'], 500);
        }
        $user->password = $plain;
        $user->passport_path = $app->passport_path; // keep uploaded path
        $user->id_card_path = $app->id_card_path;
        $user->proof_of_address_path = $app->proof_of_address_path;
        // Persist BVN verification results
        $user->bvn = $bvn;
        $user->bvn_verified_at = now();
        $user->save();

        $user->virtualAccount()->create([
            'dva_verification_meta' => [
                'provider' => $kyc['provider'] ?? null,
                'status' => $kyc['status'] ?? null,
                'score' => $kyc['score'] ?? null,
                'meta' => $kyc['meta'] ?? null,
            ]
        ]);
        $app->finalized_at = now();
        $app->user_id = $user->id; // Link to the newly created user
        $app->approval_status = 'pending'; // Ensure application is also marked as pending
        $app->save();

        // Notify relevant admins about new member registration
        $user->getAuthorizedAdmins()->each(function ($admin) use ($user) {
            $admin->notifyMember(
                "New Member Registration",
                "{$user->full_name} has completed KYC registration and is pending approval (Membership: {$user->membership_number}).",
                ['type' => 'new_member', 'user_id' => $user->id]
            );
        });

        return response()->json([
            'message' => 'Registration submitted successfully. Your account is pending admin approval.',
            'membership_number' => $user->membership_number,
            'branch_id' => $user->branch_id,
        ]);
    }

    /** Registration status helper for resuming onboarding */
    public function status(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token' => ['required', 'string', Rule::exists('member_applications', 'token')],
        ]);

        if ($validator->fails()) {
            Log::warning('Registration status check validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);
            $validator->validate();
        }

        $app = MemberApplication::where('token', $request->input('token'))->firstOrFail();

        $docs = [
            'passport_path' => $app->passport_path,
            'id_card_path' => $app->id_card_path,
            'proof_of_address_path' => $app->proof_of_address_path,
        ];
        $emailVerified = !empty($app->email_verified_at);
        $phoneVerified = !empty($app->phone_verified_at);
        $expiresIn = 0;
        if ($app->otp_expires_at && now()->lessThan($app->otp_expires_at)) {
            $expiresIn = $app->otp_expires_at->diffInSeconds(now());
        }

        return response()->json([
            'message' => 'ok',
            'application' => array_merge($docs, [
                'email_verified' => $emailVerified,
                'phone_verified' => $phoneVerified,
                'masked_email' => $app->email ? $this->maskEmail($app->email) : null,
                'masked_phone' => $app->phone ? $this->maskPhone($app->phone) : null,
                'seconds_to_expiry' => $expiresIn,
                'finalized' => !empty($app->finalized_at),
            ]),
        ]);
    }


    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $len = strlen($digits);
        if ($len <= 4) return str_repeat('*', max(0, $len - 2)).substr($digits, -2);
        return str_repeat('*', max(0, $len - 4)).substr($digits, -4);
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) return '***';
        $name = $parts[0];
        $domain = $parts[1];
        $show = max(1, (int) floor(strlen($name) * 0.3));
        return substr($name, 0, $show).str_repeat('*', max(0, strlen($name) - $show)).'@'.$domain;
    }
}
