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
        return response()->json([
            'paystack_customer_code' => $user->paystack_customer_code,
            'account_number' => $user->dva_account_number,
            'account_name' => $user->dva_account_name,
            'bank_name' => $user->dva_bank_name,
        ]);
    }

    public function assign(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'preferred_bank' => 'nullable|string', // e.g., wema-bank, titan-paystack
            'phone' => 'nullable|string',
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
            // Ensure a Paystack customer exists
            if (! $user->paystack_customer_code) {
                $payload = [
                    'email' => $user->email,
                    'first_name' => $user->name,
                ];
                if (! empty($validated['phone'])) {
                    $payload['phone'] = $validated['phone'];
                }

                $resp = $client->post('https://api.paystack.co/customer', $payload);
                if (! $resp->ok() || $resp->json('status') !== true) {
                    Log::error('Failed to create Paystack customer', ['body' => $resp->json()]);
                    return response()->json(['message' => 'Failed to create customer'], 502);
                }

                $user->paystack_customer_code = $resp->json('data.customer_code');
                $user->save();
            }

            // Assign a Dedicated Virtual Account to this customer
            $assignPayload = [
                'customer' => $user->paystack_customer_code,
            ];
            if (! empty($validated['preferred_bank'])) {
                $assignPayload['preferred_bank'] = $validated['preferred_bank'];
            }

            $assignResp = $client->post('https://api.paystack.co/dedicated_account/assign', $assignPayload);

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

            // Persist account details
            $user->dva_account_number = $acc['account_number'] ?? null;
            $user->dva_account_name = $acc['account_name'] ?? null;
            $user->dva_bank_name = ($acc['bank']['name'] ?? ($acc['bank_name'] ?? null));
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
