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
            'account_number' => 'nullable|string',
            'bank_code' => 'nullable|string',
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

        if (!empty($validated['account_number']) && $validated['account_number'] !== $user->account_number) {
            $user->update(['account_number' => $validated['account_number']]);
        }

        if (!empty($validated['bank_code']) && $validated['bank_code'] !== $user->bank_code) {
            $user->update(['bank_code' => $validated['bank_code']]);
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

                    // If they already have a dedicated account, save it and we're done
                    $dva = $fetchResp->json('data.dedicated_account');
                    if ($dva && !empty($dva['account_number'])) {
                        $user->virtualAccount()->updateOrCreate([], [
                            'paystack_customer_code' => $customerCode,
                            'dva_account_number' => $dva['account_number'],
                            'dva_account_name'   => $dva['account_name'],
                            'dva_bank_name'      => $dva['bank']['name'],
                        ]);
                        Log::info('Paystack Sync: Step B - Existing DVA recovered', ['acc' => $dva['account_number']]);
                        return $this->show($request);
                    }

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

                        // Check for DVA here too
                        $dva = $fetchResp->json('data.dedicated_account');
                        if ($dva && !empty($dva['account_number'])) {
                            $user->virtualAccount()->updateOrCreate([], [
                                'paystack_customer_code' => $customerCode,
                                'dva_account_number' => $dva['account_number'],
                                'dva_account_name'   => $dva['account_name'],
                                'dva_bank_name'      => $dva['bank']['name'],
                            ]);
                            return $this->show($request);
                        }

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
                return response()->json(['message' => $errorMsg], 422);
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
                    Log::info('Paystack Identification successful', ['code' => $customerCode]);
                }
            }

            // 4. Assign the Dedicated Virtual Account
            $assignPayload = [
                'customer'       => $customerCode,
                'preferred_bank' => $validated['preferred_bank'] ?? (config('services.paystack.mode') === 'live' ? 'titan-paystack' : 'test-bank'),
            ];

            $assignResp = Http::withToken($secret)->post('https://api.paystack.co/dedicated_account', $assignPayload);

            // 5. Handle the Response
            if ($assignResp->successful()) {
                $accData = $assignResp->json('data');

                $user->virtualAccount()->updateOrCreate([], [
                    'dva_account_number' => $accData['account_number'],
                    'dva_account_name'   => $accData['account_name'],
                    'dva_bank_name'      => $accData['bank']['name'],
                ]);

                $user->update([
                    'bvn' => $validated['bvn'] ?? $user->bvn
                ]);

                return $this->show($request);
            }

            // If assignment fails, try single-step fallback (especially if it was a customer code issue or identification required)
            if ($assignResp->json('code') === 'invalid_customer_code' || $assignResp->status() === 422 || str_contains($assignResp->body(), 'identified')) {
                Log::info('Paystack Sync: Multi-step failed, trying single-step fallback', [
                    'user_id' => $user->id,
                    'code' => $customerCode,
                    'resp' => $assignResp->json()
                ]);

                $fallbackData = [
                    'email'          => $user->email,
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'phone'          => $phone,
                    'preferred_bank' => $validated['preferred_bank'] ?? (config('services.paystack.mode') === 'live' ? 'titan-paystack' : 'test-bank'),
                    'country'        => 'NG',
                    'bvn'            => $bvn,
                ];

                if ($user->other_names) {
                    $fallbackData['middle_name'] = $user->other_names;
                }

                if ($user->account_number) {
                    $fallbackData['account_number'] = $user->account_number;
                }

                if ($user->bank_code) {
                    $fallbackData['bank_code'] = $user->bank_code;
                }

                $assignResp = Http::withToken($secret)->post('https://api.paystack.co/dedicated_account/assign', $fallbackData);

                if ($assignResp->successful()) {
                    $accData = $assignResp->json('data');
                    if (!empty($accData['account_number'])) {
                        $user->virtualAccount()->updateOrCreate([], [
                            'dva_account_number' => $accData['account_number'],
                            'dva_account_name'   => $accData['account_name'],
                            'dva_bank_name'      => $accData['bank']['name'],
                            'paystack_customer_code' => $accData['customer']['customer_code'] ?? $customerCode,
                        ]);
                        return $this->show($request);
                    }

                    return response()->json([
                        'message' => 'Virtual account creation is in progress. You will be notified once it is ready.',
                        'status' => 'processing'
                    ], 202);
                }
            }

            // If still failed, log and return error
            Log::error('DVA Assignment failed', [
                'mode' => config('services.paystack.mode'),
                'body' => $assignResp->body()
            ]);
            $errorMessage = $assignResp->json('message') ?? 'Could not assign virtual account';
            $statusCode = $assignResp->status() === 422 ? 422 : 503;
            return response()->json(['message' => $errorMessage], $statusCode);

        } catch (\Throwable $e) {
            Log::error('DVA Exception', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Requery the Paystack Dedicated Virtual Account for missed transactions.
     */
    public function requeryPaystack(Request $request)
    {
        $user = $request->user();
        $virtualAccount = $user->virtualAccount;

        if (!$virtualAccount || !$virtualAccount->dva_account_number) {
            return response()->json(['message' => 'No virtual account found to requery'], 404);
        }

        $secret = config('services.paystack.secret_key');
        if (!$secret) {
            return response()->json(['message' => 'Paystack not configured'], 500);
        }

        // Determine provider slug from bank name
        $bankName = strtolower($virtualAccount->dva_bank_name);
        $providerSlug = 'wema-bank'; // default
        if (str_contains($bankName, 'titan')) {
            $providerSlug = 'titan-paystack';
        }

        $accountNumber = $virtualAccount->dva_account_number;

        try {
            $resp = Http::withToken($secret)->get("https://api.paystack.co/dedicated_account/requery", [
                'account_number' => $accountNumber,
                'provider_slug'  => $providerSlug,
            ]);

            if ($resp->successful()) {
                return response()->json([
                    'message' => 'Requery successful. Any pending transactions will be processed via webhook shortly.',
                    'data' => $resp->json('data')
                ]);
            }

            return response()->json([
                'message' => $resp->json('message') ?? 'Requery failed',
                'details' => $resp->json('data')
            ], $resp->status());

        } catch (\Throwable $e) {
            Log::error('Paystack Requery Exception', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Could not connect to Paystack'], 503);
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

        if (empty($user->phone)) {
            return response()->json(['message' => 'Phone number is required. Please update your profile.'], 422);
        }

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
