<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveDvaService
{
    /**
     * Create a Flutterwave dedicated virtual account for the given user.
     *
     * @param  User   $user
     * @param  string|null $bvn  Optional BVN for KYC
     * @return array  ['success' => bool, 'data' => [...] | null, 'message' => string]
     */
    public function createVirtualAccount(User $user, ?string $bvn = null): array
    {
        $secret = config('services.flutterwave.secret_key');
        if (!$secret) {
            return ['success' => false, 'data' => null, 'message' => 'Payment provider not configured'];
        }

        // If user already has a Flutterwave DVA, return it
        if ($user->flw_dva_account_number) {
            return [
                'success' => true,
                'data' => [
                    'account_number' => $user->flw_dva_account_number,
                    'account_name' => $user->flw_dva_account_name,
                    'bank_name' => $user->flw_dva_bank_name,
                    'bank_code' => $user->flw_dva_bank_code,
                ],
                'message' => 'Virtual account already exists',
            ];
        }

        // Build payload for Flutterwave Create Virtual Account Number API
        $parts = preg_split('/\s+/', trim((string) $user->name));
        $firstName = $parts[0] ?? 'Member';
        $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'Coop';

        $txRef = 'DVA_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        $payload = [
            'email' => $user->email,
            'is_permanent' => true,
            'bvn' => $bvn ?? $user->bvn,
            'tx_ref' => $txRef,
            'phonenumber' => $user->phone,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'narration' => $firstName . ' ' . $lastName,
        ];

        // BVN is required by Flutterwave for Nigerian virtual accounts
        if (empty($payload['bvn'])) {
            return ['success' => false, 'data' => null, 'message' => 'BVN is required to create a Flutterwave virtual account.'];
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->timeout(30)
                ->connectTimeout(10)
                ->post('https://api.flutterwave.com/v3/virtual-account-numbers', $payload);

            if (!$response->ok() || $response->json('status') !== 'success') {
                $errorMsg = $response->json('message') ?? 'Could not create virtual account';
                Log::error('Flutterwave DVA creation failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return ['success' => false, 'data' => null, 'message' => $errorMsg];
            }

            $data = $response->json('data');

            $user->update([
                'flw_dva_account_number' => $data['account_number'] ?? null,
                'flw_dva_account_name' => $data['account_name'] ?? null,
                'flw_dva_bank_name' => $data['bank_name'] ?? null,
                'flw_dva_bank_code' => $data['bank_code'] ?? null,
                'flw_dva_order_ref' => $data['order_ref'] ?? $txRef,
                'flw_dva_flw_ref' => $data['flw_ref'] ?? null,
                'bvn' => $bvn ?? $user->bvn,
            ]);

            Log::info('Flutterwave DVA created', [
                'user_id' => $user->id,
                'account_number' => $data['account_number'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
            ]);

            return [
                'success' => true,
                'data' => [
                    'account_number' => $data['account_number'] ?? null,
                    'account_name' => $data['account_name'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                ],
                'message' => 'Virtual account created successfully',
            ];
        } catch (\Throwable $e) {
            Log::error('Flutterwave DVA exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'data' => null, 'message' => 'An unexpected error occurred.'];
        }
    }
}
