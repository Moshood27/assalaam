<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonnifyService
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $contractCode;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.monnify.api_key');
        $this->secretKey = config('services.monnify.secret_key');
        $this->contractCode = config('services.monnify.contract_code');
        $this->baseUrl = rtrim(config('services.monnify.base_url'), '/');
    }

    /**
     * Get the bearer token from Monnify.
     */
    public function getToken(): ?string
    {
        $auth = base64_encode("{$this->apiKey}:{$this->secretKey}");

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$auth}",
            ])->post("{$this->baseUrl}/api/v1/auth/login");

            if ($response->successful()) {
                return $response->json('responseBody.accessToken');
            }

            Log::error('Monnify Auth Failed', ['body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Monnify Auth Exception', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Initialize a transaction.
     */
    public function initializeTransaction(array $data)
    {
        $token = $this->getToken();
        if (!$token) return null;

        $payload = [
            'amount' => $data['amount'],
            'customerName' => $data['customerName'],
            'customerEmail' => $data['customerEmail'],
            'paymentReference' => $data['paymentReference'],
            'paymentDescription' => $data['paymentDescription'] ?? 'Payment',
            'currencyCode' => 'NGN',
            'contractCode' => $this->contractCode,
            'redirectUrl' => $data['redirectUrl'],
            'paymentMethods' => ["CARD", "ACCOUNT_TRANSFER"]
        ];

        try {
            $response = Http::withToken($token)->post("{$this->baseUrl}/api/v1/merchant/transactions/init-transaction", $payload);

            if ($response->successful()) {
                return $response->json('responseBody');
            }

            Log::error('Monnify Transaction Init Failed', ['body' => $response->body(), 'payload' => $payload]);
        } catch (\Exception $e) {
            Log::error('Monnify Transaction Init Exception', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Verify a transaction.
     */
    public function verifyTransaction(string $paymentReference)
    {
        $token = $this->getToken();
        if (!$token) return null;

        try {
            $response = Http::withToken($token)->get("{$this->baseUrl}/api/v2/merchant/transactions/query", [
                'paymentReference' => $paymentReference
            ]);

            if ($response->successful()) {
                return $response->json('responseBody');
            }

            Log::error('Monnify Transaction Verification Failed', ['body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Monnify Transaction Verification Exception', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Create/Assign a Reserved Account (DVA).
     */
    public function createVirtualAccount(User $user)
    {
        $token = $this->getToken();
        if (!$token) return ['success' => false, 'message' => 'Monnify Authentication failed'];

        $parts = preg_split('/\s+/', trim((string)$user->name));
        $firstName = $parts[0] ?? 'Member';
        $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'Coop';

        $virtualAccount = $user->virtualAccount;
        $customerReference = $virtualAccount->monnify_customer_reference ?? 'MON_' . $user->id . '_' . time();

        $payload = [
            'accountReference' => 'REF_' . $user->id . '_' . time(),
            'accountName' => "{$firstName} {$lastName}",
            'currencyCode' => 'NGN',
            'contractCode' => $this->contractCode,
            'customerEmail' => $user->email,
            'customerName' => "{$firstName} {$lastName}",
            'getAllAvailableBanks' => true,
            'customerReference' => $customerReference
        ];

        try {
            $response = Http::withToken($token)->post("{$this->baseUrl}/api/v2/bank-transfer/reserved-accounts", $payload);

            if ($response->successful()) {
                $data = $response->json('responseBody');

                // Usually Monnify returns an array of accounts if getAllAvailableBanks is true
                $accounts = $data['accounts'] ?? [];
                // We pick the first one for the main display or store all in monnify_dva_data

                $user->virtualAccount()->updateOrCreate([], [
                    'monnify_customer_reference' => $customerReference,
                    'monnify_dva_data' => $data
                ]);

                return ['success' => true, 'data' => $data];
            }

            Log::error('Monnify DVA Assignment Failed', ['body' => $response->body(), 'payload' => $payload]);
            return ['success' => false, 'message' => $response->json('responseMessage') ?? 'Monnify DVA Assignment Failed'];
        } catch (\Exception $e) {
            Log::error('Monnify DVA Assignment Exception', ['msg' => $e->getMessage()]);
            return ['success' => false, 'message' => 'An unexpected error occurred during Monnify DVA assignment.'];
        }
    }
}
