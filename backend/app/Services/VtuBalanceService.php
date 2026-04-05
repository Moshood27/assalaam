<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VtuBalanceService
{
    public function getBalances(): array
    {
        $balances = [];

        // VTpass
        $vt = $this->fetchVtpassBalance();
        if ($vt !== null) { $balances['vtpass'] = $vt; }

        // ClubKonnect
        $ck = $this->fetchClubKonnectBalance();
        if ($ck !== null) { $balances['clubkonnect'] = $ck; }

        // Shago
        $sh = $this->fetchShagoBalance();
        if ($sh !== null) { $balances['shago'] = $sh; }

        return $balances;
    }

    public function fetchVtpassBalance(): ?array
    {
        $baseUrl = rtrim(config('services.vtu.base_url', 'https://vtpass.com/api'), '/');
        $apiKey = config('services.vtu.api_key');
        $publicKey = config('services.vtu.public_key');
        $secretKey = config('services.vtu.secret_key');

        if (!$apiKey || (!$publicKey && !$secretKey)) {
            return null; // not configured
        }

        $headers = [ 'api-key' => $apiKey ];
        if ($publicKey) { $headers['public-key'] = $publicKey; }
        if ($secretKey) { $headers['secret-key'] = $secretKey; }

        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(10)
                ->retry(1, 200)
                ->get($baseUrl . '/balance');
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('VTpass balance bad response', ['status' => $resp->status(), 'body' => $json]);
                return [ 'ok' => false, 'raw' => $json ];
            }
            // Common VTpass balance shape: {"code":"000","content":{"balance":12345.67}}
            $amount = (float) ($json['content']['balance'] ?? ($json['balance'] ?? 0));
            $currency = (string) ($json['content']['currency'] ?? ($json['currency'] ?? 'NGN'));
            return [ 'ok' => true, 'available' => $amount, 'currency' => $currency, 'raw' => $json ];
        } catch (\Throwable $e) {
            Log::error('VTpass balance HTTP error', ['error' => $e->getMessage()]);
            return [ 'ok' => false, 'error' => 'network' ];
        }
    }

    public function fetchClubKonnectBalance(): ?array
    {
        $cfg = config('services.vtu.clubkonnect', []);
        if (empty($cfg['enabled']) || empty($cfg['base_url']) || (empty($cfg['api_key']) && (empty($cfg['username']) || empty($cfg['password'])))) {
            return null; // not configured
        }
        $baseUrl = rtrim((string) $cfg['base_url'], '/');
        $headers = [];
        if (!empty($cfg['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $cfg['api_key'];
        }
        if (!empty($cfg['username'])) {
            $headers['X-CK-Username'] = $cfg['username'];
            $headers['X-CK-Password'] = $cfg['password'] ?? '';
        }
        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(10)
                ->retry(1, 200)
                ->get($baseUrl . '/balance');
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('ClubKonnect balance bad response', ['status' => $resp->status(), 'body' => $json]);
                return [ 'ok' => false, 'raw' => $json ];
            }
            // Attempt common shapes
            $amount = (float) ($json['data']['balance'] ?? ($json['balance'] ?? ($json['wallet_balance'] ?? 0)));
            $currency = (string) ($json['data']['currency'] ?? ($json['currency'] ?? 'NGN'));
            return [ 'ok' => true, 'available' => $amount, 'currency' => $currency, 'raw' => $json ];
        } catch (\Throwable $e) {
            Log::error('ClubKonnect balance HTTP error', ['error' => $e->getMessage()]);
            return [ 'ok' => false, 'error' => 'network' ];
        }
    }

    public function fetchShagoBalance(): ?array
    {
        $cfg = config('services.vtu.shago', []);
        if (empty($cfg['enabled']) || empty($cfg['base_url']) || empty($cfg['api_key'])) {
            return null; // not configured
        }
        $baseUrl = rtrim((string) $cfg['base_url'], '/');
        $headers = [ 'Authorization' => 'Bearer ' . $cfg['api_key'] ];
        if (!empty($cfg['secret'])) { $headers['X-Secret'] = $cfg['secret']; }
        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(10)
                ->retry(1, 200)
                ->get($baseUrl . '/balance');
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('Shago balance bad response', ['status' => $resp->status(), 'body' => $json]);
                return [ 'ok' => false, 'raw' => $json ];
            }
            $amount = (float) ($json['data']['balance'] ?? ($json['balance'] ?? ($json['wallet_balance'] ?? 0)));
            $currency = (string) ($json['data']['currency'] ?? ($json['currency'] ?? 'NGN'));
            return [ 'ok' => true, 'available' => $amount, 'currency' => $currency, 'raw' => $json ];
        } catch (\Throwable $e) {
            Log::error('Shago balance HTTP error', ['error' => $e->getMessage()]);
            return [ 'ok' => false, 'error' => 'network' ];
        }
    }
}
