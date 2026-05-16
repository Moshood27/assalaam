<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookCall;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Paystack webhooks.
     */
    public function handlePaystack(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $secret = config('services.paystack.secret_key');

        if (!$signature || ($signature !== hash_hmac('sha512', $request->getContent(), (string)$secret))) {
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $this->dispatchWebhook('paystack', $request);

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave webhooks.
     */
    public function handleFlutterwave(Request $request)
    {
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.secret_hash');

        if (!$secretHash || !$signature || !hash_equals((string)$secretHash, (string)$signature)) {
            Log::warning('Flutterwave webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $this->dispatchWebhook('flutterwave', $request);

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Monnify webhooks.
     */
    public function handleMonnify(Request $request)
    {
        $signature = $request->header('x-monnify-signature');
        $secret = config('services.monnify.secret_key');

        if (!$signature || ($signature !== hash_hmac('sha512', $request->getContent(), (string)$secret))) {
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $this->dispatchWebhook('monnify', $request);

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Opay webhooks.
     */
    public function handleOpay(Request $request)
    {
        $signature = $request->header('Authorization') ?? $request->header('X-Opay-Signature');
        if (str_starts_with((string)$signature, 'Bearer ')) {
            $signature = substr($signature, 7);
        }

        $secret = config('services.opay.secret_key');
        $computed = hash_hmac('sha512', $request->getContent(), (string)$secret);

        if (!$signature || !hash_equals($signature, $computed)) {
            Log::warning('Opay webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            // Still dispatch for investigation if needed, or reject?
            // Rejecting is better for scaling to avoid DB bloat from invalid requests.
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $this->dispatchWebhook('opay', $request);

        return response()->json(['status' => 'success']);
    }

    /**
     * Dispatch webhook to background job.
     */
    protected function dispatchWebhook(string $provider, Request $request): void
    {
        $payload = $request->all();

        // Extract external ID if possible for easier lookup
        $externalId = $this->extractExternalId($provider, $payload);

        $webhookCall = WebhookCall::create([
            'provider' => $provider,
            'external_id' => $externalId,
            'payload' => $payload,
            'headers' => $request->headers->all(),
            'status' => 'pending',
        ]);

        ProcessWebhookJob::dispatch($webhookCall);
    }

    /**
     * Extract reference/ID from payload for indexing.
     */
    protected function extractExternalId(string $provider, array $payload): ?string
    {
        switch ($provider) {
            case 'paystack':
                return $payload['data']['reference'] ?? null;
            case 'flutterwave':
                return $payload['data']['tx_ref'] ?? $payload['data']['txRef'] ?? null;
            case 'monnify':
                return $payload['eventData']['paymentReference'] ?? null;
            case 'opay':
                return $payload['reference'] ?? $payload['orderNo'] ?? null;
            default:
                return null;
        }
    }
}
