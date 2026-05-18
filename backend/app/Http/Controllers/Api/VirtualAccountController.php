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
            'paystack_customer_code' => $virtualAccount->paystack_customer_code ?? null,
            'account_number' => $virtualAccount->dva_account_number ?? null,
            'account_name' => $virtualAccount->dva_account_name ?? null,
            'bank_name' => $virtualAccount->dva_bank_name ?? null,
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
        $parts = preg_split('/\s+/', trim((string)$user->name));
        $firstName = $parts[0] ?? 'Member';
        $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'Coop';

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
                $lastResp = Http::withToken($secret)->put("https://api.paystack.co/customer/{$customerCode}", $customerPayload);
                if ($lastResp->successful()) {
                    $syncSuccess = true;
                    $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                } else {
                    // If update failed (e.g. invalid code/environment mismatch), clear it and try email lookup
                    $customerCode = null;
                }
            }

            // Step B: If no code or update failed, try fetching by email
            if (!$syncSuccess) {
                $fetchResp = Http::withToken($secret)->get("https://api.paystack.co/customer/" . urlencode($user->email));
                if ($fetchResp->successful() && $fetchResp->json('data.customer_code')) {
                    $customerCode = $fetchResp->json('data.customer_code');
                    // Now update it to sync phone/names
                    $lastResp = Http::withToken($secret)->put("https://api.paystack.co/customer/{$customerCode}", $customerPayload);
                    $syncSuccess = $lastResp->successful();
                    if ($syncSuccess) {
                        $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                    }
                } else {
                    // Step C: If not found on Paystack, create the customer
                    $lastResp = Http::withToken($secret)->post('https://api.paystack.co/customer', array_merge($customerPayload, [
                        'email' => $user->email,
                    ]));
                    if ($lastResp->successful()) {
                        $customerCode = $lastResp->json('data.customer_code');
                        $syncSuccess = true;
                    }
                }
            }

            if (!$syncSuccess || empty($customerCode)) {
                $errorMsg = ($lastResp) ? ($lastResp->json('message') ?? 'Identity sync failed') : 'Identity sync failed';
                Log::error('Paystack Customer sync failed', [
                    'user_id' => $user->id,
                    'body' => $lastResp?->body() ?? 'No response'
                ]);
                return response()->json(['message' => $errorMsg], 502);
            }

            // Save/Update the customer code locally
            $user->virtualAccount()->updateOrCreate([], ['paystack_customer_code' => $customerCode]);

            // 4. Assign the Dedicated Virtual Account
            $assignPayload = [
                'customer'       => $customerCode,
                'preferred_bank' => $validated['preferred_bank'] ?? 'titan-paystack',
            ];

            if (!empty($validated['bvn'])) {
                $assignPayload['bvn'] = $validated['bvn'];
            }

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

            // If assignment fails, log why
            Log::error('DVA Assignment failed', ['body' => $assignResp->body()]);
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
