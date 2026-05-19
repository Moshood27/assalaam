<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlutterwaveDvaService;
use App\Services\MonnifyService;
use App\Services\OpayService;
use App\Services\PaystackDvaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class VirtualAccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $virtualAccount = $user->virtualAccount;
        $verificationDetails = null;
        if ($virtualAccount && $virtualAccount->dva_bank_name && $virtualAccount->dva_account_number) {
            $verificationDetails = $virtualAccount->dva_bank_name . ' - ' . $virtualAccount->dva_account_number;
            if (!empty($virtualAccount->dva_account_name)) {
                $verificationDetails .= ' (' . $virtualAccount->dva_account_name . ')';
            }
        }

        $flwVerificationDetails = null;
        if ($user->flw_dva_bank_name && $user->flw_dva_account_number) {
            $flwVerificationDetails = $user->flw_dva_bank_name . ' - ' . $user->flw_dva_account_number;
            if (!empty($user->flw_dva_account_name)) {
                $flwVerificationDetails .= ' (' . $user->flw_dva_account_name . ')';
            }
        }

        $monnifyVerificationDetails = null;
        if ($user->monnify_dva_bank_name && $user->monnify_dva_account_number) {
            $monnifyVerificationDetails = $user->monnify_dva_bank_name . ' - ' . $user->monnify_dva_account_number;
            if (!empty($user->monnify_dva_account_name)) {
                $monnifyVerificationDetails .= ' (' . $user->monnify_dva_account_name . ')';
            }
        }

        $opayVerificationDetails = null;
        if ($user->opay_dva_bank_name && $user->opay_dva_account_number) {
            $opayVerificationDetails = $user->opay_dva_bank_name . ' - ' . $user->opay_dva_account_number;
            if (!empty($user->opay_dva_account_name)) {
                $opayVerificationDetails .= ' (' . $user->opay_dva_account_name . ')';
            }
        }

        return response()->json([
            'paystack_customer_code' => $virtualAccount?->paystack_customer_code,
            'account_number' => $virtualAccount?->dva_account_number,
            'account_name' => $virtualAccount?->dva_account_name,
            'bank_name' => $virtualAccount?->dva_bank_name,
            'bvn_assigned' => (bool) ($user->bvn || $user->bvn_verified_at || ($virtualAccount && $virtualAccount->dva_account_number && $virtualAccount->dva_bank_name)),
            'verification_details' => $verificationDetails,
            // Flutterwave DVA
            'flw_account_number' => $user->flw_dva_account_number,
            'flw_account_name' => $user->flw_dva_account_name,
            'flw_bank_name' => $user->flw_dva_bank_name,
            'flw_verification_details' => $flwVerificationDetails,
            // Monnify DVA
            'monnify_account_number' => $user->monnify_dva_account_number,
            'monnify_account_name' => $user->monnify_dva_account_name,
            'monnify_bank_name' => $user->monnify_dva_bank_name,
            'monnify_verification_details' => $monnifyVerificationDetails,
            // Opay DVA
            'opay_account_number' => $user->opay_dva_account_number,
            'opay_account_name' => $user->opay_dva_account_name,
            'opay_bank_name' => $user->opay_dva_bank_name,
            'opay_verification_details' => $opayVerificationDetails,
        ]);
    }

    public function assign(Request $request, PaystackDvaService $paystackDvaService)
    {
        $user = $request->user();
        $email = trim((string)$user->email);
        $secret = config('services.paystack.secret_key');

        if (!$secret) {
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        // 1. Validate Basic Requirements
        $validated = $request->validate([
            'preferred_bank' => 'nullable|string',
            'phone' => 'nullable|string',
            'bvn' => 'nullable|string|digits:11',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
        ]);

        $phone = $validated['phone'] ?? $user->phone;
        if (empty($phone)) {
            return response()->json(['message' => 'Phone number is required. Please update your profile.'], 422);
        }

        // 2. Prepare Name Data
        $firstName = $validated['first_name'] ?? $user->name;
        $lastName = $validated['last_name'] ?? $user->surname;
        if (empty($lastName) && empty($validated['last_name'])) {
            $parts = preg_split('/\s+/', trim((string)$user->name));
            $firstName = $parts[0] ?? 'Member';
            $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'Coop';
        }

        if (!empty($validated['bvn']) && $validated['bvn'] !== $user->bvn) {
            $user->update(['bvn' => $validated['bvn']]);
        }

        try {
            // 3. Sync/Upsert Customer
            $customerPayload = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
            ];

            $syncResp = $paystackDvaService->syncCustomer($user, $customerPayload);
            if (!$syncResp['success'] || empty($syncResp['customer_code'])) {
                Log::error('Paystack Customer sync failed', ['user_id' => $user->id, 'resp' => $syncResp['response']]);
                return response()->json(['message' => $syncResp['response']['message'] ?? 'Identity sync failed'], 502);
            }
            $customerCode = $syncResp['customer_code'];
            $isIdentified = $syncResp['identified'];

            // 3.5 Customer Identification
            $bvn = $validated['bvn'] ?? $user->bvn;
            if ($bvn) {
                if (!$isIdentified) {
                    $identResp = $paystackDvaService->submitIdentification($user, $customerCode, $bvn, [
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ]);

                    if (!$identResp['success'] && !$identResp['already_identified']) {
                        if ($identResp['status'] === 400 || $identResp['status'] === 422) {
                            return response()->json([
                                'message' => $identResp['response']['message'] ?? 'Identity verification failed',
                                'details' => $identResp['response']['meta'] ?? null
                            ], 422);
                        }
                    }
                    if ($identResp['already_identified']) {
                        $isIdentified = true;
                    }
                }

                if (!$isIdentified) {
                    // Return early, webhook will handle the rest
                    return response()->json([
                        'message' => 'Identity verification is processing. Your virtual account will be assigned automatically once verified.',
                        'status' => 'processing'
                    ], 202);
                }
            }

            // 4. Assign Dedicated Virtual Account
            $assignPayload = [
                'preferred_bank' => $validated['preferred_bank'] ?? 'titan-paystack',
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'phone'          => $phone,
                'email'          => $email,
                'bvn'            => $bvn,
            ];

            $assignResp = $paystackDvaService->assignDva($user, $customerCode, $assignPayload);

            if ($assignResp['success']) {
                $user->update(['bvn' => $bvn]);
                return $this->show($request);
            }

            // Fallback: check existing
            if ($paystackDvaService->fetchExistingDva($user, $customerCode)) {
                $user->update(['bvn' => $bvn]);
                return $this->show($request);
            }

            // Handle errors
            if ($assignResp['status'] === 422) {
                $isIdentificationIssue = $assignResp['is_identification_issue'];

                if (!$isIdentificationIssue) {
                    $user->virtualAccount()->update(['paystack_customer_code' => null]);
                    Log::warning('Paystack rejected customer code during assignment - cleared locally', [
                        'user_id' => $user->id,
                        'customer_code' => $customerCode,
                        'response' => $assignResp['response']
                    ]);
                }

                $userMsg = $isIdentificationIssue
                    ? 'Identity verification is still processing at Paystack. Your account will be ready shortly.'
                    : 'Identity verification issue. We have reset your record, please try again.';

                return response()->json(['message' => $userMsg], 422);
            }

            Log::error('DVA Assignment failed', ['resp' => $assignResp['response']]);
            return response()->json(['message' => $assignResp['response']['message'] ?? 'Could not assign virtual account'], 502);

        } catch (\Throwable $e) {
            Log::error('DVA Exception', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Create a Flutterwave DVA for the authenticated user.
     */
    public function assignFlutterwave(Request $request)
    {
        $validated = $request->validate([
            'bvn' => 'sometimes|required|string|digits:11',
        ]);

        $user = $request->user();
        $service = app(FlutterwaveDvaService::class);
        $result = $service->createVirtualAccount($user, $validated['bvn'] ?? null);

        if (!$result['success']) {
            $status = str_contains($result['message'], 'not configured') ? 500 : 422;
            return response()->json(['message' => $result['message']], $status);
        }

        return $this->show($request);
    }

    /**
     * Regenerate a Flutterwave DVA for the authenticated user.
     */
    public function regenerateFlutterwave(Request $request)
    {
        $validated = $request->validate([
            'bvn' => 'sometimes|required|string|digits:11',
        ]);

        $user = $request->user();
        $service = app(FlutterwaveDvaService::class);
        $result = $service->createVirtualAccount($user, $validated['bvn'] ?? null, true);

        if (!$result['success']) {
            $status = str_contains($result['message'], 'not configured') ? 500 : 422;
            return response()->json(['message' => $result['message']], $status);
        }

        return $this->show($request);
    }

    /**
     * Create a Monnify DVA for the authenticated user.
     */
    public function assignMonnify(Request $request)
    {
        $user = $request->user();
        $service = app(MonnifyService::class);
        $result = $service->createVirtualAccount($user);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return $this->show($request);
    }

    /**
     * Create an Opay DVA for the authenticated user.
     */
    public function assignOpay(Request $request)
    {
        $user = $request->user();
        $service = app(OpayService::class);
        $result = $service->createVirtualAccount($user);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return $this->show($request);
    }

    /**
     * Delete the Paystack record for the authenticated user.
     */
    public function deletePaystack(Request $request)
    {
        $user = $request->user();

        // Clear User fields
        $userUpdate = ['autosave_enabled' => false];
        foreach (['paystack_customer_code', 'paystack_authorization_code', 'dva_account_number', 'dva_bank_name', 'dva_account_name'] as $col) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', $col)) {
                $userUpdate[$col] = null;
            }
        }
        $user->update($userUpdate);

        // Clear Virtual Account fields
        if ($user->virtualAccount) {
            $user->virtualAccount->update([
                'paystack_customer_code' => null,
                'paystack_authorization_code' => null,
                'dva_account_number' => null,
                'dva_bank_name' => null,
                'dva_account_name' => null,
                'dva_verification_meta' => null,
            ]);
        }

        return response()->json(['message' => 'Paystack record and Autosave cleared successfully']);
    }
}
