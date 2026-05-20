<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secret;

    public function __construct()
    {
        $this->secret = (string) config('services.paystack.secret_key');
    }

    /**
     * Standardize names for Paystack
     */
    public function standardizeNames(User $user): array
    {
        $firstName = trim(strtoupper((string)($user->name ?? '')));
        $lastName = trim(strtoupper((string)($user->surname ?? '')));

        if (empty($lastName)) {
            $parts = explode(' ', $firstName);
            $firstName = $parts[0] ?? 'MEMBER';
            $lastName = $parts[1] ?? 'COOP';
        }

        return [$firstName, $lastName];
    }

    /**
     * Ensure customer exists and is synced on Paystack
     */
    public function syncCustomer(User $user, ?string $phone = null): array
    {
        [$firstName, $lastName] = $this->standardizeNames($user);
        $phone = $phone ?? $user->phone;

        try {
            $fetchResp = Http::withToken($this->secret)->get("https://api.paystack.co/customer/" . urlencode($user->email));

            if ($fetchResp->successful() && $fetchResp->json('data')) {
                $paystackData = $fetchResp->json('data');
                $customerCode = $paystackData['customer_code'];

                // Update local copy if needed
                $user->virtualAccount()->updateOrCreate([], ['paystack_customer_code' => $customerCode]);

                return [
                    'success' => true,
                    'customer_code' => $customerCode,
                    'data' => $paystackData
                ];
            }

            // Create customer if they don't exist
            $createResp = Http::withToken($this->secret)->post('https://api.paystack.co/customer', [
                'email' => $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);

            if (!$createResp->successful()) {
                return ['success' => false, 'message' => 'Customer creation failed: ' . ($createResp->json('message') ?? 'Unknown error')];
            }

            $customerCode = $createResp->json('data.customer_code');
            $user->virtualAccount()->updateOrCreate([], ['paystack_customer_code' => $customerCode]);

            return [
                'success' => true,
                'customer_code' => $customerCode,
                'data' => $createResp->json('data')
            ];

        } catch (\Throwable $e) {
            Log::error('Paystack syncCustomer exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'An unexpected error occurred during customer sync.'];
        }
    }

    /**
     * Submit identification for a customer
     */
    public function submitIdentification(User $user, string $customerCode, string $bvn): array
    {
        [$firstName, $lastName] = $this->standardizeNames($user);

        try {
            $identResp = Http::withToken($this->secret)->post("https://api.paystack.co/customer/{$customerCode}/identification", [
                'country' => 'NG',
                'type' => 'bvn',
                'value' => $bvn,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            $identData = $identResp->json();

            if (!$identResp->successful() && !str_contains($identData['message'] ?? '', 'already')) {
                return ['success' => false, 'message' => 'Identification Error: ' . ($identData['message'] ?? 'Unknown error')];
            }

            return ['success' => true, 'message' => $identData['message'] ?? 'Identification submitted successfully'];

        } catch (\Throwable $e) {
            Log::error('Paystack submitIdentification exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'An unexpected error occurred during identification.'];
        }
    }

    /**
     * Assign Dedicated Virtual Account
     */
    public function assignDva(User $user, string $customerCode, ?string $preferredBank = 'wema-bank'): array
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                $assignResp = Http::withToken($this->secret)->post('https://api.paystack.co/dedicated_account', [
                    'customer' => $customerCode,
                    'preferred_bank' => $preferredBank ?? 'wema-bank',
                ]);

                if ($assignResp->successful()) {
                    $accData = $assignResp->json('data');

                    $user->virtualAccount()->updateOrCreate([], [
                        'dva_account_number' => $accData['account_number'],
                        'dva_account_name'   => $accData['account_name'],
                        'dva_bank_name'      => $accData['bank']['name'] ?? ($accData['provider']['name'] ?? 'Bank'),
                    ]);

                    return ['success' => true, 'data' => $accData];
                }

                $message = $assignResp->json('message') ?? 'Could not assign virtual account';

                // Handle specific case where Paystack says "not identified" even if we just did it
                // We retry a few times because of Paystack's internal propagation delay
                if (str_contains(strtolower($message), 'identified') && $attempt < $maxRetries) {
                    Log::info("Paystack DVA Assignment: Customer not yet identified. Retry attempt $attempt/3.", ['user_id' => $user->id]);
                    sleep(3 * $attempt); // Increasing sleep: 3s, 6s
                    continue;
                }

                if ($attempt >= $maxRetries && str_contains(strtolower($message), 'identified')) {
                    return [
                        'success' => false,
                        'pending_kyc' => true,
                        'message' => 'Paystack is still processing your KYC. Please try again in a few minutes.'
                    ];
                }

                return ['success' => false, 'message' => $message];

            } catch (\Throwable $e) {
                Log::error('Paystack assignDva exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                if ($attempt >= $maxRetries) {
                    return ['success' => false, 'message' => 'An unexpected error occurred during DVA assignment.'];
                }
                sleep(2);
            }
        }

        return ['success' => false, 'message' => 'DVA assignment failed after multiple attempts.'];
    }

    /**
     * Verify a transaction reference
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $resp = Http::withToken($this->secret)
                ->acceptJson()
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(3, 300)
                ->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));

            if (!$resp->ok() || ($resp->json('status') !== true)) {
                return ['success' => false, 'message' => $resp->json('message') ?? 'Verification call failed'];
            }

            return ['success' => true, 'data' => $resp->json('data')];

        } catch (\Throwable $e) {
            Log::error('Paystack verifyTransaction exception', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'An unexpected error occurred during verification.'];
        }
    }
}
