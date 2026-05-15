<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpayService
{
    protected string $merchantId;
    protected string $publicKey;
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.opay.merchant_id');
        $this->publicKey = config('services.opay.public_key');
        $this->secretKey = config('services.opay.secret_key');
        $this->baseUrl = rtrim(config('services.opay.base_url'), '/');
    }

    /**
     * Initialize a transaction (Checkout).
     */
    public function initializeTransaction(array $data)
    {
        $payload = [
            'merchantId' => $this->merchantId,
            'amount' => [
                'total' => (string)round($data['amount'] * 100), // Opay often expects kobo/minor units as string
                'currency' => 'NGN'
            ],
            'reference' => $data['reference'],
            'returnUrl' => $data['callbackUrl'],
            'callbackUrl' => $data['callbackUrl'],
            'cancelUrl' => $data['callbackUrl'],
            'userIp' => request()->ip() ?? '127.0.0.1',
            'expireAt' => 30,
            'productDesc' => $data['paymentDescription'] ?? 'Payment',
            'userName' => $data['customerName'] ?? 'Member',
            'userEmail' => $data['customerEmail'] ?? ''
        ];

        try {
            $signature = hash_hmac('sha512', json_encode($payload), $this->secretKey);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$signature}",
                'MerchantId' => $this->merchantId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/cashier/create", $payload);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Opay Transaction Init Failed', ['body' => $response->body(), 'payload' => $payload]);
        } catch (\Exception $e) {
            Log::error('Opay Transaction Init Exception', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Verify a transaction.
     */
    public function verifyTransaction(string $reference)
    {
        $payload = [
            'merchantId' => $this->merchantId,
            'reference' => $reference,
        ];

        try {
            $signature = hash_hmac('sha512', json_encode($payload), $this->secretKey);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$signature}",
                'MerchantId' => $this->merchantId,
            ])->post("{$this->baseUrl}/transaction/status", $payload);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Opay Transaction Verification Failed', ['body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Opay Transaction Verification Exception', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Create/Assign a Virtual Account (DVA).
     */
    public function createVirtualAccount(User $user)
    {
        $virtualAccount = $user->virtualAccount;
        // Ensure user has a virtual account record
        if (!$virtualAccount) {
            $virtualAccount = $user->virtualAccount()->create([]);
        }

        $userReference = $virtualAccount->opay_user_reference ?? 'OPY_' . $user->id . '_' . bin2hex(random_bytes(4));

        $payload = [
            'merchantId' => $this->merchantId,
            'userReference' => $userReference,
            'userName' => $user->full_name,
            'userEmail' => $user->email,
            'userPhone' => $user->phone ?? '',
        ];

        try {
            $signature = hash_hmac('sha512', json_encode($payload), $this->secretKey);

            // Opay Merchant DVA endpoint is typically /merchant/v1/user/account
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$signature}",
                'MerchantId' => $this->merchantId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/merchant/v1/user/account", $payload);

            if ($response->successful()) {
                $data = $response->json('data');

                $user->virtualAccount()->updateOrCreate([], [
                    'opay_user_reference' => $userReference,
                    'opay_dva_data' => $data
                ]);

                return ['success' => true, 'data' => $data];
            }

            Log::error('Opay DVA Assignment Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Opay DVA Assignment Failed (' . $response->status() . ')'
            ];
        } catch (\Exception $e) {
            Log::error('Opay DVA Assignment Exception', ['msg' => $e->getMessage()]);
            return ['success' => false, 'message' => 'An unexpected error occurred during Opay DVA assignment.'];
        }
    }
}
