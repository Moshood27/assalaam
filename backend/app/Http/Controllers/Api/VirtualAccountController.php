<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlutterwaveDvaService;
use App\Services\MonnifyService;
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
            // 3. Sync/Upsert Customer on Paystack using PUT with email identifier to always sync phone
            $identifier = $user->email; // Paystack accepts email or customer code as identifier
            $customerPayload = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
            ];

            $customerResp = Http::withToken($secret)->put("https://api.paystack.co/customer/{$identifier}", $customerPayload);

            $customerCode = null;
            if (!$customerResp->successful()) {
                // If not found, create the customer instead
                if ($customerResp->status() === 404) {
                    $createResp = Http::withToken($secret)->post('https://api.paystack.co/customer', array_merge($customerPayload, [
                        'email' => $user->email,
                    ]));
                    if (!$createResp->successful()) {
                        Log::error('Paystack Customer sync failed', ['put_body' => $customerResp->body(), 'post_body' => $createResp->body()]);
                        return response()->json(['message' => 'Identity sync failed'], 502);
                    }
                    $customerCode = $createResp->json('data.customer_code');
                } else {
                    Log::error('Paystack Customer sync failed', ['body' => $customerResp->body()]);
                    return response()->json(['message' => 'Identity sync failed'], 502);
                }
            } else {
                $customerCode = $customerResp->json('data.customer_code') ?? ($user->virtualAccount->paystack_customer_code ?? null);
            }

            if (!empty($customerCode)) {
                $user->virtualAccount()->updateOrCreate([], ['paystack_customer_code' => $customerCode]);
            }

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
}
