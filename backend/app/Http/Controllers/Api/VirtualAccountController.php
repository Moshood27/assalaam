<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlutterwaveDvaService;
use App\Services\MonnifyService;
use App\Services\OpayService;
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

    public function assign(Request $request)
    {
        $user = $request->user();
        $secret = config('services.paystack.secret_key');

        if (!$secret) {
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        // 1. Validate Basic Requirements
        $validated = $request->validate([
            'preferred_bank' => 'nullable|string',
            'phone' => 'nullable|string',
            'bvn' => 'nullable|string|digits:11',
        ]);

        $phone = $validated['phone'] ?? $user->phone;
        if (empty($phone)) {
            return response()->json(['message' => 'Phone number is required. Please update your profile.'], 422);
        }

        // 2. Prepare Name Data (Paystack requires First and Last name)
        $firstName = $user->name;
        $lastName = $user->surname;

        if (empty($lastName)) {
            $parts = preg_split('/\s+/', trim((string)$user->name));
            $firstName = $parts[0] ?? 'Member';
            $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'Coop';
        }

        if (!empty($validated['bvn']) && $validated['bvn'] !== $user->bvn) {
            $user->update(['bvn' => $validated['bvn']]);
        }

        try {
            // 3. Sync/Upsert Customer on Paystack
            $customerCode = $user->virtualAccount?->paystack_customer_code;
            $customerPayload = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
            ];

            $syncSuccess = false;
            $lastResp = null;

            // Step A: If we have a local code, try updating it
            if ($customerCode) {
                Log::info('Paystack Sync: Step A - Updating existing code', ['code' => $customerCode]);
                $lastResp = Http::withToken($secret)->put("https://api.paystack.co/customer/{$customerCode}", $customerPayload);
                if ($lastResp->successful()) {
                    $syncSuccess = true;
                    $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                    Log::info('Paystack Sync: Step A Success', ['code' => $customerCode]);
                } else {
                    Log::warning('Paystack Sync: Step A Failed', ['code' => $customerCode, 'resp' => $lastResp->json()]);
                    // If update failed (e.g. invalid code/environment mismatch), clear it and try email lookup
                    $customerCode = null;
                }
            }

            // Step B: If not synced, try fetching by email
            if (!$syncSuccess) {
                Log::info('Paystack Sync: Step B - Fetching by email', ['email' => $user->email]);
                $fetchResp = Http::withToken($secret)->get("https://api.paystack.co/customer/" . urlencode($user->email));
                if ($fetchResp->successful() && $fetchResp->json('data.customer_code')) {
                    $customerCode = $fetchResp->json('data.customer_code');
                    Log::info('Paystack Sync: Step B Found', ['code' => $customerCode]);
                    // Verify it with a PUT to ensure it's valid for this environment
                    $lastResp = Http::withToken($secret)->put("https://api.paystack.co/customer/{$customerCode}", $customerPayload);
                    if ($lastResp->successful()) {
                        $syncSuccess = true;
                        $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                        Log::info('Paystack Sync: Step B Verified', ['code' => $customerCode]);
                    } else {
                        Log::warning('Paystack Sync: Step B Verification Failed', ['code' => $customerCode, 'resp' => $lastResp->json()]);
                        // If the code from GET is not working for PUT, clear it to try Step C
                        $customerCode = null;
                    }
                } else {
                    Log::info('Paystack Sync: Step B Not Found', ['email' => $user->email]);
                }
            }

            // Step C: If still not synced, try creating fresh
            if (!$syncSuccess) {
                Log::info('Paystack Sync: Step C - Creating fresh customer', ['email' => $user->email]);
                $lastResp = Http::withToken($secret)->post('https://api.paystack.co/customer', array_merge($customerPayload, [
                    'email' => $user->email,
                ]));
                if ($lastResp->successful()) {
                    $customerCode = $lastResp->json('data.customer_code');
                    $syncSuccess = true;
                    Log::info('Paystack Sync: Step C Success', ['code' => $customerCode]);
                } elseif ($lastResp->json('message') === 'Customer already exists' || $lastResp->json('code') === 'duplicate_email') {
                    Log::info('Paystack Sync: Step C - Already exists, final attempt to fetch');
                    // If it exists but we couldn't create it, try one last GET + PUT to be sure
                    $fetchResp = Http::withToken($secret)->get("https://api.paystack.co/customer/" . urlencode($user->email));
                    if ($fetchResp->successful() && $fetchResp->json('data.customer_code')) {
                        $customerCode = $fetchResp->json('data.customer_code');
                        $lastResp = Http::withToken($secret)->put("https://api.paystack.co/customer/{$customerCode}", $customerPayload);
                        $syncSuccess = $lastResp->successful();
                        if ($syncSuccess) {
                            $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                            Log::info('Paystack Sync: Step C Final Sync Success', ['code' => $customerCode]);
                        } else {
                            Log::error('Paystack Sync: Step C Final Sync Failed', ['code' => $customerCode, 'resp' => $lastResp->json()]);
                        }
                    }
                } else {
                    Log::error('Paystack Sync: Step C Failed', ['resp' => $lastResp->json()]);
                }
            }

            if (!$syncSuccess || empty($customerCode)) {
                $errorMsg = ($lastResp) ? ($lastResp->json('message') ?? 'Identity sync failed') : 'Identity sync failed';
                Log::error('Paystack Customer sync failed', [
                    'user_id' => $user->id,
                    'mode' => config('services.paystack.mode'),
                    'body' => $lastResp?->body() ?? 'No response'
                ]);
                return response()->json(['message' => $errorMsg], 502);
            }

            // Save/Update the customer code locally
            $user->virtualAccount()->updateOrCreate([], ['paystack_customer_code' => $customerCode]);

            // 3.5 Customer Identification (Required for some banks like Titan Trust)
            $bvn = $validated['bvn'] ?? $user->bvn;
            if ($bvn) {
                Log::info('Paystack Sync: Identification step', ['code' => $customerCode]);
                $identResp = Http::withToken($secret)->post("https://api.paystack.co/customer/{$customerCode}/identification", [
                    'country'    => 'NG',
                    'type'       => 'bvn',
                    'value'      => $bvn,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                ]);

                if (!$identResp->successful()) {
                    $identData = $identResp->json();
                    Log::warning('Paystack Identification failed', ['code' => $customerCode, 'resp' => $identData]);

                    // If it's not already identified, and it's a Titan Trust request, we might want to stop here
                    if (($identData['message'] ?? '') !== 'Customer already identified') {
                        // Some errors (like name mismatch) are fatal for DVA
                        if ($identResp->status() === 400 || $identResp->status() === 422) {
                             return response()->json([
                                 'message' => $identData['message'] ?? 'Identity verification failed',
                                 'details' => $identData['meta'] ?? null
                             ], 422);
                        }
                    }
                } else {
                    Log::info('Paystack Identification successful', ['code' => $customerCode, 'resp' => $identResp->json()]);
                }
            }

            // 4. Assign the Dedicated Virtual Account
            $assignPayload = [
                'customer'       => $customerCode,
                'preferred_bank' => $validated['preferred_bank'] ?? 'titan-paystack',
            ];

            // Note: For /dedicated_account, bvn is not a documented parameter.
            // We've already done identification above if bvn was provided.
            // However, for some banks/categories, identification is async and
            // /dedicated_account might fail if called too soon.

            $assignResp = Http::withToken($secret)->post('https://api.paystack.co/dedicated_account', $assignPayload);

            // 4.5 Fallback to /assign if /dedicated_account fails (e.g. identification pending)
            // But skip fallback if it's just "not identified" and we don't have bank details to fulfill the /assign requirements
            $canFallback = true;
            if ($assignResp->json('message') === 'Customer has not been identified' && empty($user->account_number)) {
                $canFallback = false;
            }

            if (!$assignResp->successful() && $canFallback && ($assignResp->json('code') === 'invalid_customer_code' || $assignResp->status() === 400)) {
                Log::info('Paystack DVA: Primary assignment failed, trying /assign fallback', [
                    'user_id' => $user->id,
                    'customer_code' => $customerCode,
                    'resp' => $assignResp->json()
                ]);

                $fallbackPayload = [
                    'email'          => $user->email,
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'phone'          => $phone,
                    'preferred_bank' => $validated['preferred_bank'] ?? 'titan-paystack',
                    'country'        => 'NG',
                ];
                $fallbackBvn = $validated['bvn'] ?? $user->bvn;
                if (!empty($fallbackBvn)) {
                    $fallbackPayload['bvn'] = $fallbackBvn;
                    // If we have bank details, include them as they are often required for /assign with BVN
                    if ($user->account_number && $user->bank_code) {
                        $fallbackPayload['account_number'] = $user->account_number;
                        $fallbackPayload['bank_code'] = $user->bank_code;
                    }
                }

                $assignResp = Http::withToken($secret)->post('https://api.paystack.co/dedicated_account/assign', $fallbackPayload);

                Log::info('Paystack DVA: Fallback /assign result', [
                    'user_id' => $user->id,
                    'status' => $assignResp->status(),
                    'body' => $assignResp->json()
                ]);
            }

            // 5. Handle the Response
            if ($assignResp->successful()) {
                $accData = $assignResp->json('data');

                if ($accData && isset($accData['account_number'])) {
                    $user->virtualAccount()->updateOrCreate([], [
                        'dva_account_number' => $accData['account_number'],
                        'dva_account_name'   => $accData['account_name'],
                        'dva_bank_name'      => $accData['bank']['name'],
                    ]);
                } elseif (!$user->virtualAccount?->dva_account_number) {
                    // If no account number in response and none in DB, it's likely async processing
                    return response()->json([
                        'message' => 'Paystack is setting up your virtual account. We will notify you once it is ready.',
                        'status' => 'processing'
                    ], 202);
                }

                $user->update([
                    'bvn' => $validated['bvn'] ?? $user->bvn
                ]);

                return $this->show($request);
            }

            // If assignment fails, handle specific errors
            if ($assignResp->json('code') === 'invalid_customer_code') {
                if ($assignResp->json('message') === 'Customer has not been identified') {
                    return response()->json(['message' => 'Paystack is still verifying your identity. Please wait a minute and try again.'], 202);
                }

                // Only clear if it's truly an invalid code (e.g. not found)
                $user->virtualAccount()->update(['paystack_customer_code' => null]);
                Log::warning('Paystack rejected customer during assignment - cleared locally', [
                    'user_id' => $user->id,
                    'customer_code' => $customerCode,
                    'resp' => $assignResp->json(),
                    'mode' => config('services.paystack.mode')
                ]);
                return response()->json(['message' => 'Identity verification issue. We have reset your record, please try again.'], 422);
            }

            Log::error('DVA Assignment failed', [
                'mode' => config('services.paystack.mode'),
                'body' => $assignResp->body()
            ]);
            $errorMessage = $assignResp->json('message') ?? 'Could not assign virtual account';
            return response()->json(['message' => $errorMessage], 502);

        } catch (\Throwable $e) {
            Log::error('DVA Exception', ['msg' => $e->getMessage()]);
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
