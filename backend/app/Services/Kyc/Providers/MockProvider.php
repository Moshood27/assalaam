<?php

namespace App\Services\Kyc\Providers;

class MockProvider
{
    /**
     * Simulate BVN + face verification.
     * Rules:
     *  - 11-digit BVN is required
     *  - Accept if BVN ends with an even digit
     *  - Score is 0.92 for accepted, 0.35 for rejected
     */
    public function verifyBvnWithFace(string $bvn, string $selfiePath, ?string $idImagePath = null): array
    {
        $bvn = preg_replace('/[^0-9]/', '', $bvn ?? '');
        if (strlen($bvn) !== 11) {
            return [
                'success' => false,
                'status' => 'invalid_bvn',
                'score' => 0.0,
                'provider' => 'mock',
                'meta' => [
                    'message' => 'BVN must be 11 digits',
                ],
            ];
        }
        $last = (int) substr($bvn, -1);
        $ok = ($last % 2) === 0; // even wins
        return [
            'success' => $ok,
            'status' => $ok ? 'verified' : 'not_matched',
            'score' => $ok ? 0.92 : 0.35,
            'provider' => 'mock',
            'meta' => [
                'note' => 'Mocked response for local/dev',
                'bvn' => $bvn,
                'selfie_exists' => is_file(public_path($selfiePath)),
                'id_image_exists' => $idImagePath ? is_file(public_path($idImagePath)) : false,
                'hint' => $ok ? null : 'In mock mode, BVNs ending with an even digit pass.'
            ],
        ];
    }
}
