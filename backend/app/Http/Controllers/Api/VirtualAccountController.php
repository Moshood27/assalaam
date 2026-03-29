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

        $validated = $request->validate([
            'preferred_bank' => 'nullable|string', // e.g., wema-bank, titan-paystack
            'phone' => 'nullable|string',
            'bvn' => 'nullable|string|digits:11',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $secret = config('services.paystack.secret_key');
        if (! $secret) {
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        // If already assigned, just return existing details
        if ($user->dva_account_number && $user->dva_bank_name) {
            return $this->show($request);
        }

        // Prepare HTTP client with retries and timeouts
        $client = Http::withToken($secret)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(3, 300);

        try {
            // Ensure we have a phone number (Paystack requires it on the Customer for DVA)
            $effectivePhone = $validated['phone'] ?? ($user->phone ?? null);
            if (empty($effectivePhone)) {
                return response()->json(['message' => 'Phone number is required to assign a virtual account. Please add your phone number to your profile or provide it in this request.'], 422);
            }
            // Ensure we have an email (required by Paystack for DVA and customer)
            $effectiveEmail = $validated['email'] ?? ($user->email ?? null);
            if (empty($effectiveEmail)) {
                return response()->json(['message' => 'Email is required to assign a virtual account. Please add your email to your profile or provide it in this request.'], 422);
            }
            // Ensure a Paystack customer exists
            if (! $user->paystack_customer_code) {
                $payload = [
                    'email' => $effectiveEmail,
                ];
                // Use provided first/last name if available; otherwise attempt to split user's name
                if (!empty($validated['first_name']) || !empty($validated['last_name'])) {
                    if (!empty($validated['first_name'])) { $payload['first_name'] = $validated['first_name']; }
                    if (!empty($validated['last_name'])) { $payload['last_name'] = $validated['last_name']; }
                } else {
                    $parts = preg_split('/\s+/', trim((string)$user->name));
                    $payload['first_name'] = $parts[0] ?? ($user->name ?: '');
                    if (count($parts) > 1) { $payload['last_name'] = implode(' ', array_slice($parts, 1)); }
                }
                // Always include a phone number for the customer record
                $payload['phone'] = $effectivePhone;

                $resp = $client->post('https://api.paystack.co/customer', $payload);
                if (! $resp->ok() || $resp->json('status') !== true) {
                    Log::error('Failed to create Paystack customer', ['body' => $resp->json()]);
                    return response()->json(['message' => 'Failed to create customer'], 502);
                }

                $user->paystack_customer_code = $resp->json('data.customer_code');
                $user->save();
            }

            // Update Paystack customer to ensure phone/email are set (required for DVA)
            try {
                $updatePayload = [
                    'phone' => $effectivePhone,
                ];
                // Include email if available
                if (!empty($validated['email']) || !empty($user->email)) {
                    $updatePayload['email'] = $validated['email'] ?? $user->email;
                }
                // Optionally include names
                if (!empty($validated['first_name']) || !empty($validated['last_name'])) {
                    if (!empty($validated['first_name'])) { $updatePayload['first_name'] = $validated['first_name']; }
                    if (!empty($validated['last_name'])) { $updatePayload['last_name'] = $validated['last_name']; }
                } else {
                    $parts = preg_split('/\s+/', trim((string)$user->name));
                    if (!empty($parts[0])) { $updatePayload['first_name'] = $parts[0]; }
                    if (count($parts) > 1) { $updatePayload['last_name'] = implode(' ', array_slice($parts, 1)); }
                }

                $updateResp = $client->put('https://api.paystack.co/customer/' . $user->paystack_customer_code, $updatePayload);
                if (! $updateResp->ok() || $updateResp->json('status') !== true) {
                    Log::warning('Failed to update Paystack customer with phone/email before DVA assign', [
                        'code' => $user->paystack_customer_code,
                        'body' => $updateResp->json(),
                    ]);
                }
            } catch (ConnectionException $e) {
                Log::warning('Network error while updating Paystack customer before DVA assign', [
                    'code' => $user->paystack_customer_code,
                    'error' => $e->getMessage(),
                ]);
            }

            // Assign a Dedicated Virtual Account to this customer (use email per Paystack guidance)
            $assignPayload = [
                'customer' => $effectiveEmail,
            ];
            if (! empty($validated['preferred_bank'])) {
                $assignPayload['preferred_bank'] = $validated['preferred_bank'];
            }

            $assignResp = $client->post('https://api.paystack.co/dedicated_account', $assignPayload);
            // If customer not found using email, retry using customer_code
            if (! $assignResp->ok() || $assignResp->json('status') !== true) {
                $message = strtolower((string) ($assignResp->json('message') ?? ''));
                if ($assignResp->status() === 404 || str_contains($message, 'customer not found')) {
                    $retryPayload = $assignPayload;
                    $retryPayload['customer'] = $user->paystack_customer_code;
                    $assignResp = $client->post('https://api.paystack.co/dedicated_account', $retryPayload);
                }
            }

            if (! $assignResp->ok() || $assignResp->json('status') !== true) {
                // Fallback: fetch existing assigned DVAs for this customer (may already be assigned)
                Log::warning('Assign DVA failed, attempting to fetch existing', [
                    'code' => $user->paystack_customer_code,
                    'body' => $assignResp->json(),
                ]);

                $fetchResp = $client->get('https://api.paystack.co/dedicated_account', [
                    'customer' => $user->paystack_customer_code,
                ]);

                if (! $fetchResp->ok() || $fetchResp->json('status') !== true) {
                    Log::error('Failed to fetch existing Paystack DVAs', ['body' => $fetchResp->json()]);
                    return response()->json(['message' => 'Failed to assign virtual account'], 502);
                }

                $list = $fetchResp->json('data', []);
                $acc = is_array($list) && ! empty($list) ? $list[0] : null;
                if (! $acc) {
                    return response()->json(['message' => 'No virtual accounts found for this customer'], 502);
                }
            } else {
                $data = $assignResp->json('data');
                // Some responses nest data under 'data.data', handle both
                if (isset($data['account_number'])) {
                    $acc = $data;
                } else {
                    $acc = $assignResp->json('data.data', []);
                }
            }

            // Persist account details and optional BVN
            $user->dva_account_number = $acc['account_number'] ?? null;
            $user->dva_account_name = $acc['account_name'] ?? null;
            $user->dva_bank_name = ($acc['bank']['name'] ?? ($acc['bank_name'] ?? null));
            if (!empty($validated['bvn']) && empty($user->bvn)) {
                $user->bvn = $validated['bvn'];
            }
            // Store minimal verification meta for audit/visibility
            $user->dva_verification_meta = [
                'provider' => 'paystack',
                'customer_code' => $user->paystack_customer_code,
                'account_number' => $acc['account_number'] ?? null,
                'account_name' => $acc['account_name'] ?? null,
                'bank' => [
                    'id' => $acc['bank']['id'] ?? ($acc['bank_id'] ?? null),
                    'name' => $acc['bank']['name'] ?? ($acc['bank_name'] ?? null),
                ],
                'assigned_at' => now()->toISOString(),
            ];
            $user->save();

            return $this->show($request);
        } catch (ConnectionException $e) {
            Log::error('Connection error while communicating with Paystack', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Network error while assigning virtual account. Please try again.'], 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error during DVA assignment', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to assign virtual account'], 500);
        }
    }
}
