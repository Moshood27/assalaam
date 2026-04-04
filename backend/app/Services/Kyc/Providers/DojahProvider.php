<?php

namespace App\Services\Kyc\Providers;

use Illuminate\Support\Facades\Http;

class DojahProvider
{
    /**
     * Verify BVN with face match using Dojah APIs.
     * Minimal implementation: BVN lookup + selfie vs BVN image match.
     * Returns a normalized array.
     */
    public function verifyBvnWithFace(string $bvn, string $selfiePath, ?string $idImagePath = null): array
    {
        $bvn = preg_replace('/[^0-9]/', '', $bvn ?? '');
        if (strlen($bvn) !== 11) {
            return [
                'success' => false,
                'status' => 'invalid_bvn',
                'score' => 0.0,
                'provider' => 'dojah',
                'meta' => ['message' => 'BVN must be 11 digits'],
            ];
        }

        $appId = config('kyc.dojah.app_id');
        $secret = config('kyc.dojah.secret');
        $base = rtrim(config('kyc.dojah.base_url', 'https://api.dojah.io'), '/');
        $confidencePass = (float) config('kyc.thresholds.face_match_min', 0.82);

        if (!$appId || !$secret) {
            return [
                'success' => false,
                'status' => 'misconfigured',
                'score' => 0.0,
                'provider' => 'dojah',
                'meta' => ['message' => 'Missing Dojah credentials'],
            ];
        }

        // 1) Get BVN details (to check BVN validity and fetch portrait if available)
        $bvnResp = Http::withHeaders([
            'AppId' => $appId,
            'Authorization' => 'Bearer '.$secret,
            'Content-Type' => 'application/json',
        ])->timeout(20)->get($base.'/api/v1/identities/bvn', [
            'bvn' => $bvn,
        ]);

        if (!$bvnResp->ok()) {
            return [
                'success' => false,
                'status' => 'provider_error',
                'score' => 0.0,
                'provider' => 'dojah',
                'meta' => [
                    'http_status' => $bvnResp->status(),
                    'body' => $bvnResp->json(),
                ],
            ];
        }
        $bvnData = $bvnResp->json();

        // 2) Face match - compare user selfie with BVN image
        // Dojah face compare expects two images; some accounts allow URL upload. We'll send base64.
        $selfieAbs = $this->toAbsolutePath($selfiePath);
        if (!is_file($selfieAbs)) {
            return [
                'success' => false,
                'status' => 'missing_selfie',
                'score' => 0.0,
                'provider' => 'dojah',
                'meta' => ['message' => 'Selfie image not found'],
            ];
        }
        $selfieB64 = base64_encode(file_get_contents($selfieAbs));

        // If BVN image comes from the BVN data, use it; else fallback to uploaded ID image only if it is an image type.
        $bvnImageBase64 = $bvnData['entity']['image'] ?? null; // may vary by account
        $referenceSource = null;
        if ($bvnImageBase64) {
            $referenceSource = 'bvn';
        } elseif ($idImagePath) {
            $idAbs = $this->toAbsolutePath($idImagePath);
            if (is_file($idAbs) && $this->isImagePath($idAbs)) {
                $bvnImageBase64 = base64_encode(file_get_contents($idAbs));
                $referenceSource = 'id_card';
            }
        }
        if (!$bvnImageBase64) {
            return [
                'success' => false,
                'status' => 'no_reference_image',
                'score' => 0.0,
                'provider' => 'dojah',
                'meta' => [
                    'message' => 'No reference image available for match',
                    'id_card_was_image' => isset($idAbs) ? ($this->isImagePath($idAbs) ? true : false) : null,
                ],
            ];
        }

        $compareResp = Http::withHeaders([
            'AppId' => $appId,
            'Authorization' => 'Bearer '.$secret,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($base.'/api/v1/kyc/face/compare', [
            'image_one' => $selfieB64,
            'image_two' => $bvnImageBase64,
        ]);

        if (!$compareResp->ok()) {
            return [
                'success' => false,
                'status' => 'provider_error',
                'score' => 0.0,
                'provider' => 'dojah',
                'meta' => [
                    'http_status' => $compareResp->status(),
                    'body' => $compareResp->json(),
                ],
            ];
        }
        $cmp = $compareResp->json();
        $score = (float) ($cmp['entity']['score'] ?? $cmp['entity']['confidence'] ?? 0);
        $passed = $score >= $confidencePass;

        return [
            'success' => $passed,
            'status' => $passed ? 'verified' : 'not_matched',
            'score' => $score,
            'provider' => 'dojah',
            'meta' => [
                'bvn' => $bvn,
                'bvn_lookup' => $bvnData,
                'compare' => $cmp,
                'threshold' => $confidencePass,
                'reference_source' => $referenceSource,
            ],
        ];
    }

    private function toAbsolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) return $path;
        if (preg_match('/^[A-Za-z]:\\\\/', $path)) return $path; // Windows absolute
        return public_path($path);
    }

    private function isImagePath(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
    }
}
