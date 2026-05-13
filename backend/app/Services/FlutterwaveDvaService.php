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
     * @param  bool $force  Whether to force creation of a new account even if one exists
     * @return array  ['success' => bool, 'data' => [...] | null, 'message' => string]
     */
    public function createVirtualAccount(User $user, ?string $bvn = null, bool $force = false): array
    {
        $secret = config('services.flutterwave.secret_key');
        if (!$secret) {
            return ['success' => false, 'data' => null, 'message' => 'Payment provider not configured'];
        }

        // If user already has a Flutterwave DVA, return it (unless forcing regeneration)
        if (!$force && $user->flw_dva_account_number) {
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

        $bvnToUse = $bvn ?? $user->bvn;
        if (empty($bvnToUse)) {
            return ['success' => false, 'data' => null, 'message' => 'BVN is required to create a Flutterwave virtual account.'];
        }

        // Always validate BVN for member when creating DVA flutterwave
        $validation = $this->validateBvn($bvnToUse, $user);
        if (!$validation['success']) {
            return ['success' => false, 'data' => null, 'message' => $validation['message']];
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

            $user->virtualAccount()->updateOrCreate([], [
                'flw_dva_data' => [
                    'account_number' => $data['account_number'] ?? null,
                    'account_name' => $data['account_name'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                    'order_ref' => $data['order_ref'] ?? $txRef,
                    'flw_ref' => $data['flw_ref'] ?? null,
                ],
            ]);

            $user->update([
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
    /**
     * Validate BVN details against the user's profile.
     */
    public function validateBvn(string $bvn, User $user): array
    {
        // If already verified with this BVN, skip API call
        if ($user->bvn === $bvn && $user->bvn_verified_at) {
            return ['success' => true, 'message' => 'BVN already verified'];
        }

        // For local/testing with mock provider, we simulate validation
        if (config('kyc.provider') === 'mock') {
            $last = (int) substr($bvn, -1);
            if (($last % 2) !== 0) {
                return ['success' => false, 'message' => 'BVN validation failed (Mock: only even digits pass)'];
            }
            return ['success' => true, 'message' => 'BVN validated (Mock)'];
        }

        $secret = config('services.flutterwave.secret_key');
        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->timeout(20)
                ->get("https://api.flutterwave.com/v3/kyc/bvns/{$bvn}");

            if (!$response->ok()) {
                $errorMsg = $response->json('message') ?? 'BVN validation service unavailable';
                Log::error('Flutterwave BVN validation API error', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
                return ['success' => false, 'message' => $errorMsg];
            }

            $bvnData = $response->json('data');
            if (!$this->namesMatch($bvnData, $user)) {
                Log::warning('Flutterwave BVN name mismatch', [
                    'user_id' => $user->id,
                    'bvn_data' => $bvnData,
                    'user_name' => $user->name
                ]);
                return [
                    'success' => false,
                    'message' => 'BVN name mismatch. The identity on this BVN does not match your profile name.'
                ];
            }

            // Mark as verified if matched
            $user->update([
                'bvn' => $bvn,
                'bvn_verified_at' => now()
            ]);

            return ['success' => true, 'message' => 'BVN validated successfully'];

        } catch (\Throwable $e) {
            Log::error('Flutterwave BVN validation exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Unexpected error during BVN validation'];
        }
    }

    /**
     * Compare names loosely.
     */
    protected function namesMatch(array $bvnData, User $user): bool
    {
        $bvnFirst = strtolower(trim($bvnData['first_name'] ?? ''));
        $bvnLast = strtolower(trim($bvnData['last_name'] ?? ''));

        if (empty($bvnFirst) && empty($bvnLast)) return false;

        $fullName = strtolower($user->name);
        // Use word boundaries for matching to avoid partial matches (e.g. "And" matching "Anderson")
        $firstMatch = empty($bvnFirst) || (bool)preg_match('/\b' . preg_quote($bvnFirst, '/') . '\b/i', $fullName);
        $lastMatch = empty($bvnLast) || (bool)preg_match('/\b' . preg_quote($bvnLast, '/') . '\b/i', $fullName);

        // Fallback check against surname and other_names fields
        if (!$lastMatch && !empty($user->surname)) {
            $lastMatch = strtolower($user->surname) === $bvnLast;
        }
        if (!$firstMatch && !empty($user->other_names)) {
            $firstMatch = (bool)preg_match('/\b' . preg_quote($bvnFirst, '/') . '\b/i', strtolower($user->other_names));
        }

        return $firstMatch && $lastMatch;
    }
}
