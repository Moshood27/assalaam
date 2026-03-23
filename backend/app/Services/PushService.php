<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;

class PushService
{
    public function enabled(): bool
    {
        return (bool) config('push.enabled', false);
    }

    /**
    * Send a push notification to a single device token.
    * Returns true on success, false on failure. Never throws.
    */
    public function send(?string $toToken, string $title, string $body, array $data = []): bool
    {
        if (!$this->enabled()) {
            Log::info('[PUSH disabled] '.($toToken ?: 'no-token').': '.$title.' | '.$body, ['data' => $data]);
            return false;
        }
        $token = trim((string) $toToken);
        if ($token === '') {
            Log::warning('Push not sent: missing device token');
            return false;
        }

        $driver = (string) config('push.driver', 'fcm');
        try {
            if ($driver === 'log') {
                $channel = config('push.log.channel');
                Log::channel($channel ?: config('logging.default'))
                    ->info('[PUSH log driver] '.$title.' | '.$body, ['to' => $token, 'data' => $data]);
                return true;
            }
            if ($driver === 'fcm_v1') {
                return $this->sendViaFcmV1($token, $title, $body, $data);
            }
            if ($driver === 'fcm') {
                return $this->sendViaFcmLegacy($token, $title, $body, $data);
            }
            Log::warning('Push driver not recognized', ['driver' => $driver]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('Push send threw', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Prepare Firebase Admin SDK credentials for Kreait Messaging client.
     *
     * Supports the following envs (in order of precedence):
     * - FIREBASE_CREDENTIALS_JSON: Inline JSON content of the service account
     * - FIREBASE_CREDENTIALS_BASE64: Base64-encoded JSON content
     * - FIREBASE_CREDENTIALS or GOOGLE_APPLICATION_CREDENTIALS: Path to JSON (absolute or relative to base_path())
     * - Fallback to storage/app/firebase_credentials.json if it exists
     */
    protected function prepareFirebaseCredentials(): bool
    {
        try {
            $defaultPath = storage_path('app/firebase_credentials.json');

            $jsonInline = trim((string) env('FIREBASE_CREDENTIALS_JSON', ''));
            if ($jsonInline !== '') {
                $this->ensureDirectory(dirname($defaultPath));
                file_put_contents($defaultPath, $jsonInline);
                $this->applyFirebaseCredentialsPath($defaultPath);
                return true;
            }

            $b64 = trim((string) env('FIREBASE_CREDENTIALS_BASE64', ''));
            if ($b64 !== '') {
                $decoded = base64_decode($b64, true);
                if ($decoded !== false) {
                    $this->ensureDirectory(dirname($defaultPath));
                    file_put_contents($defaultPath, $decoded);
                    $this->applyFirebaseCredentialsPath($defaultPath);
                    return true;
                }
            }

            $path = (string) (env('FIREBASE_CREDENTIALS') ?: env('GOOGLE_APPLICATION_CREDENTIALS') ?: '');
            $path = trim($path);

            if ($path === '') {
                if (is_file($defaultPath)) {
                    $this->applyFirebaseCredentialsPath($defaultPath);
                    return true;
                }
                return false;
            }

            // If the env contains raw JSON (starts with '{'), write it to default path
            if (str_starts_with(ltrim($path), '{')) {
                $this->ensureDirectory(dirname($defaultPath));
                file_put_contents($defaultPath, $path);
                $this->applyFirebaseCredentialsPath($defaultPath);
                return true;
            }

            // Resolve relative paths against the Laravel base path
            $resolved = $path;
            $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR);
            if (!$isAbsolute) {
                $resolved = base_path($path);
            }

            if (!is_file($resolved)) {
                // Try fallback default if it exists
                if (is_file($defaultPath)) {
                    $this->applyFirebaseCredentialsPath($defaultPath);
                    return true;
                }
                Log::warning('Firebase credentials file not found', ['path' => $resolved]);
                return false;
            }

            $this->applyFirebaseCredentialsPath($resolved);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to prepare Firebase credentials', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function applyFirebaseCredentialsPath(string $path): void
    {
        // Set both env and runtime config for Kreait
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$path);
        $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $path; // ensure visibility for packages reading $_ENV
        config(['firebase.projects.app.credentials' => $path]);
    }

    protected function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    /**
     * Latest FCM HTTP v1 send via Service Account (kreait/laravel-firebase)
     */
    protected function sendViaFcmV1(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            // Ensure credentials are configured before resolving the Messaging client
            if (!$this->prepareFirebaseCredentials()) {
                Log::warning('FCM v1 push not sent: Firebase credentials are not configured or file not found. Set FIREBASE_CREDENTIALS to a readable JSON file, or provide FIREBASE_CREDENTIALS_JSON/FIREBASE_CREDENTIALS_BASE64.');
                return false;
            }

            /** @var FirebaseMessaging $messaging */
            $messaging = app(FirebaseMessaging::class);

            // Ensure data values are strings as required by FCM
            $stringData = [];
            foreach ($data as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $stringData[(string) $k] = (string) ($v ?? '');
                } else {
                    $stringData[(string) $k] = json_encode($v);
                }
            }

            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
            ])
                ->withHighestPossiblePriority()
                ->withDefaultSounds();

            $messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM v1 push failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Legacy FCM HTTP API (to be deprecated)
     */
    protected function sendViaFcmLegacy(string $token, string $title, string $body, array $data = []): bool
    {
        $serverKey = (string) config('push.fcm.server_key');
        $base = rtrim((string) config('push.fcm.base_url', 'https://fcm.googleapis.com'), '/');
        if (!$serverKey) {
            Log::warning('FCM push not sent: missing server key');
            return false;
        }
        $url = $base.'/fcm/send';
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
            'priority' => 'high',
        ];
        $res = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);
        if (!$res->ok()) {
            $code = $res->status();
            $json = $res->json();
            Log::warning('FCM push failed', ['status' => $code, 'body' => $json]);
            return false;
        }
        return true;
    }
}
