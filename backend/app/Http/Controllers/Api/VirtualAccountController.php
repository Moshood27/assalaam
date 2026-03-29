<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class VirtualAccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $verificationDetails = null;
        if ($user->dva_bank_name && $user->dva_account_number) {
            $verificationDetails = $user->dva_bank_name . ' - ' . $user->dva_account_number;
            if (!empty($user->dva_account_name)) {
                $verificationDetails .= ' (' . $user->dva_account_name . ')';
            }
        }
        return response()->json([
            'paystack_customer_code' => $user->paystack_customer_code,
            'account_number' => $user->dva_account_number,
            'account_name' => $user->dva_account_name,
            'bank_name' => $user->dva_bank_name,
            'bvn_assigned' => (bool) ($user->bvn || $user->bvn_verified_at || ($user->dva_account_number && $user->dva_bank_name)),
            'verification_details' => $verificationDetails,
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
            return response()->json(['message' => 'Phone number is required for Virtual Accounts.'], 422);
        }

        // 2. Prepare Name Data (Paystack requires First and Last name)
        $parts = preg_split('/\s+/', trim((string)$user->name));
        $firstName = $parts[0] ?? 'Member';
        $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'Coop';

        try {
            // 3. Ensure Customer Exists on Paystack
            // Even if we have a customer_code, we "upsert" (create or update) to be safe
            $customerResp = Http::withToken($secret)->post('https://api.paystack.co/customer', [
                'email'      => $user->email,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
            ]);

            if (!$customerResp->successful()) {
                Log::error('Paystack Customer sync failed', ['body' => $customerResp->body()]);
                return response()->json(['message' => 'Identity verification failed on provider side'], 502);
            }

            $customerCode = $customerResp->json('data.customer_code');
            $user->update(['paystack_customer_code' => $customerCode]);

            // 4. Assign the Dedicated Virtual Account
            $assignPayload = [
                'customer'       => $customerCode,
                'preferred_bank' => $validated['preferred_bank'] ?? 'wema-bank',
            ];

            if (!empty($validated['bvn'])) {
                $assignPayload['bvn'] = $validated['bvn'];
            }

            $assignResp = Http::withToken($secret)->post('https://api.paystack.co/dedicated_account', $assignPayload);

            // 5. Handle the Response
            if ($assignResp->successful()) {
                $accData = $assignResp->json('data');

                $user->update([
                    'dva_account_number' => $accData['account_number'],
                    'dva_account_name'   => $accData['account_name'],
                    'dva_bank_name'      => $accData['bank']['name'],
                    'bvn'                => $validated['bvn'] ?? $user->bvn
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
}
