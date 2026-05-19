<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackDvaService
{
    protected string $secret;

    public function __construct()
    {
        $this->secret = (string) config('services.paystack.secret_key');
    }

    /**
     * Sync or create a customer on Paystack.
     */
    public function syncCustomer(User $user, array $payload): array
    {
        $email = trim((string)$user->email);
        $customerCode = trim((string)($user->virtualAccount?->paystack_customer_code));
        if (empty($customerCode)) $customerCode = null;

        $syncSuccess = false;
        $isIdentified = false;
        $lastResp = null;

        // Step A: Update existing
        if ($customerCode) {
            Log::info('Paystack Service: Updating customer', ['code' => $customerCode]);
            $lastResp = Http::withToken($this->secret)->put("https://api.paystack.co/customer/{$customerCode}", $payload);
            if ($lastResp->successful()) {
                $syncSuccess = true;
                $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                $isIdentified = (bool) $lastResp->json('data.identified');
            } else {
                Log::warning('Paystack Service: Update failed', ['code' => $customerCode, 'resp' => $lastResp->json()]);
                $customerCode = null;
            }
        }

        // Step B: Fetch by email
        if (!$syncSuccess) {
            Log::info('Paystack Service: Fetching by email', ['email' => $email]);
            $fetchResp = Http::withToken($this->secret)->get("https://api.paystack.co/customer/" . urlencode($email));
            if ($fetchResp->successful() && $fetchResp->json('data.customer_code')) {
                $customerCode = $fetchResp->json('data.customer_code');
                Log::info('Paystack Service: Found by email', ['code' => $customerCode]);
                $lastResp = Http::withToken($this->secret)->put("https://api.paystack.co/customer/{$customerCode}", $payload);
                if ($lastResp->successful()) {
                    $syncSuccess = true;
                    $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                    $isIdentified = (bool) $lastResp->json('data.identified');
                } else {
                    Log::warning('Paystack Service: Email verification failed', ['code' => $customerCode, 'resp' => $lastResp->json()]);
                    $customerCode = null;
                }
            }
        }

        // Step C: Create fresh
        if (!$syncSuccess) {
            Log::info('Paystack Service: Creating fresh customer', ['email' => $email]);
            $lastResp = Http::withToken($this->secret)->post('https://api.paystack.co/customer', array_merge($payload, ['email' => $email]));
            if ($lastResp->successful()) {
                $customerCode = $lastResp->json('data.customer_code');
                $syncSuccess = true;
                $isIdentified = (bool) $lastResp->json('data.identified');
            } elseif ($lastResp->json('message') === 'Customer already exists' || $lastResp->json('code') === 'duplicate_email') {
                Log::info('Paystack Service: Already exists during creation, final fetch');
                $fetchResp = Http::withToken($this->secret)->get("https://api.paystack.co/customer/" . urlencode($email));
                if ($fetchResp->successful() && $fetchResp->json('data.customer_code')) {
                    $customerCode = $fetchResp->json('data.customer_code');
                    $lastResp = Http::withToken($this->secret)->put("https://api.paystack.co/customer/{$customerCode}", $payload);
                    $syncSuccess = $lastResp->successful();
                    if ($syncSuccess) {
                        $customerCode = $lastResp->json('data.customer_code') ?? $customerCode;
                        $isIdentified = (bool) $lastResp->json('data.identified');
                    }
                }
            }
        }

        if ($syncSuccess && $customerCode) {
            $user->virtualAccount()->updateOrCreate([], ['paystack_customer_code' => $customerCode]);
        }

        return [
            'success' => $syncSuccess,
            'customer_code' => $customerCode,
            'identified' => $isIdentified,
            'response' => $lastResp ? $lastResp->json() : null,
        ];
    }

    /**
     * Submit customer for identification.
     */
    public function submitIdentification(User $user, string $customerCode, string $bvn, array $names): array
    {
        Log::info('Paystack Service: Submitting identification', ['code' => $customerCode]);
        $resp = Http::withToken($this->secret)->post("https://api.paystack.co/customer/{$customerCode}/identification", [
            'country'    => 'NG',
            'type'       => 'bvn',
            'value'      => $bvn,
            'first_name' => $names['first_name'],
            'last_name'  => $names['last_name'],
        ]);

        Log::info('Paystack Service: Identification response', [
            'code' => $customerCode,
            'status' => $resp->status(),
            'resp' => $resp->json()
        ]);

        return [
            'success' => $resp->successful(),
            'already_identified' => ($resp->json('message') === 'Customer already identified'),
            'status' => $resp->status(),
            'response' => $resp->json(),
        ];
    }

    /**
     * Assign Dedicated Virtual Account.
     */
    public function assignDva(User $user, string $customerCode, array $payload): array
    {
        Log::info('Paystack Service: Assigning DVA', ['code' => $customerCode]);
        $resp = Http::withToken($this->secret)->post('https://api.paystack.co/dedicated_account', array_merge($payload, [
            'customer' => $customerCode,
        ]));

        if ($resp->successful()) {
            $accData = $resp->json('data');
            $this->updateLocalAccount($user, $accData);
            return ['success' => true, 'data' => $accData];
        }

        // Check if identification issue
        $msg = $resp->json('message') ?? '';
        $isIdentificationIssue = str_contains(strtolower($msg), 'identif') || str_contains(strtolower($msg), 'verif');

        return [
            'success' => false,
            'is_identification_issue' => $isIdentificationIssue,
            'response' => $resp->json(),
            'status' => $resp->status(),
        ];
    }

    /**
     * Fetch existing Dedicated Virtual Account.
     */
    public function fetchExistingDva(User $user, string $customerCode): bool
    {
        Log::info('Paystack Service: Fetching existing DVA', ['code' => $customerCode]);
        $resp = Http::withToken($this->secret)->get('https://api.paystack.co/dedicated_account', [
            'customer' => $customerCode
        ]);

        if ($resp->successful() && !empty($resp->json('data'))) {
            $accounts = $resp->json('data');
            $accData = is_array($accounts) ? ($accounts[0] ?? null) : null;

            if ($accData && isset($accData['account_number'])) {
                $this->updateLocalAccount($user, $accData);
                return true;
            }
        }

        return false;
    }

    protected function updateLocalAccount(User $user, array $accData): void
    {
        $user->virtualAccount()->updateOrCreate([], [
            'dva_account_number' => $accData['account_number'],
            'dva_account_name'   => $accData['account_name'],
            'dva_bank_name'      => $accData['bank']['name'],
        ]);
    }
}
