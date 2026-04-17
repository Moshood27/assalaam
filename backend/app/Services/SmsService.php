<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function enabled(): bool
    {
        return (bool) config('sms.enabled', false);
    }

    /**
     * Send an SMS message to the given phone number.
     * Non-blocking: errors are logged and false is returned.
     */
    public function send(?string $to, string $message): bool
    {
        if (!$this->enabled()) {
            Log::info('[SMS disabled] '.($to ?? 'unknown').': '.$message);
            return false;
        }

        $to = $this->normalizeMsisdn($to);
        if (empty($to)) {
            Log::warning('SMS not sent: missing or invalid phone', ['raw_to' => $to]);
            return false;
        }

        $driver = config('sms.driver', 'termii');
        Log::debug('Attempting to send SMS', ['to' => $to, 'driver' => $driver]);
        try {
            if ($driver === 'termii') {
                return $this->sendViaTermii($to, $message);
            }
            if ($driver === 'log') {
                Log::info('[SMS log driver] '.$to.': '.$message);
                return true;
            }
            // Generic JSON POST
            $url = (string) config('sms.url');
            if (!$url) {
                Log::warning('SMS not sent: generic driver requires sms.url');
                return false;
            }
            $payload = [
                'to' => $to,
                'message' => $message,
                'sender' => config('sms.sender'),
                'api_key' => config('sms.api_key'),
            ];
            $res = Http::asJson()->post($url, $payload);
            if (!$res->ok()) {
                Log::warning('Generic SMS send failed', ['status' => $res->status(), 'body' => $res->json()]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning('SMS send threw', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function sendViaTermii(string $to, string $message): bool
    {
        $apiKey = (string) config('sms.api_key');
        $sender = (string) config('sms.sender', 'ATTAQWA');
        $base = rtrim((string) config('sms.base_url', 'https://v3.api.termii.com'), '/');
        if (!$apiKey) {
            Log::warning('Termii SMS not sent: missing api key');
            return false;
        }
        $url = $base.'/api/sms/send';
        $payload = [
            'to' => $to,
            'from' => $sender,
            'sms' => $message,
            'type' => 'plain',
            'channel' => config('sms.channel', 'generic'),
            'api_key' => $apiKey,
        ];
        $res = Http::asJson()->post($url, $payload);
        if (!$res->ok()) {
            Log::warning('Termii SMS failed', [
                'status' => $res->status(),
                'body' => $res->json(),
                'to' => $to,
            ]);
            return false;
        }

        $data = $res->json();
        // Termii sometimes returns 200 OK but with a body indicating failure (e.g. {status: "error", message: "..."})
        if (isset($data['status']) && in_array($data['status'], ['error', 'failed'])) {
             Log::warning('Termii returned error status', [
                 'body' => $data,
                 'to' => $to
             ]);
             return false;
        }

        Log::info('Termii SMS sent successfully', [
            'to' => $to,
            'message_id' => $data['message_id'] ?? $data['data']['message_id'] ?? null
        ]);
        return true;
    }

    /**
     * Normalize MSISDN to E.164-ish. Default country NG (234).
     */
    public function normalizeMsisdn(?string $msisdn, string $defaultCountry = '234'): ?string
    {
        if (!$msisdn) return null;
        $s = preg_replace('/[^0-9+]/', '', $msisdn);
        if (!$s) return null;
        // Replace leading 00 with +
        if (str_starts_with($s, '00')) {
            $s = '+'.substr($s, 2);
        }
        // If starts with +, drop plus for some providers, keep digits only
        if (str_starts_with($s, '+')) {
            $s = substr($s, 1);
        }
        // If starts with 0 and default country provided, replace 0 with country code
        if (str_starts_with($s, '0') && $defaultCountry) {
            $s = $defaultCountry.substr($s, 1);
        }
        // If already starts with country code or other, return digits
        return $s;
    }
}
