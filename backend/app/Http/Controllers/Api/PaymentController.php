<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function getSchemes()
    {
        return response()->json(Scheme::where('active', true)->orderBy('name')->get());
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.scheme_id' => 'required|exists:schemes,id',
            'items.*.amount' => 'required|numeric|min:1',
            'callback_url' => 'nullable|url',
        ]);

        $user = $request->user();

        // Sum intended distribution to schemes
        $totalAmount = collect($validated['items'])->sum(fn ($i) => (float) $i['amount']);
        if ($totalAmount <= 0) {
            return response()->json(['message' => 'Amount must be greater than zero'], 422);
        }

        // Generate unique reference for this payment
        $reference = 'COOP_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        // Pre-create pending contributions for each scheme for idempotency & distribution record
        foreach ($validated['items'] as $item) {
            $user->contributions()->create([
                'scheme_id' => $item['scheme_id'],
                'amount' => $item['amount'],
                'reference' => $reference,
                'status' => 'pending',
            ]);
        }

        // Choose payment gateway: paystack (default) or flutterwave
        $gateway = strtolower($request->input('gateway', 'paystack'));

        if ($gateway === 'flutterwave') {
            $flwSecret = config('services.flutterwave.secret_key');
            if (!$flwSecret) {
                Log::warning('Flutterwave secret key is not set');
                return response()->json(['message' => 'Payment provider not configured'], 500);
            }

            $payload = [
                'tx_ref' => $reference,
                'amount' => round($totalAmount, 2),
                'currency' => 'NGN',
                'redirect_url' => $validated['callback_url'] ?? null,
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phonenumber' => $user->phone,
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'distribution' => $validated['items'],
                ],
            ];
            if (empty($validated['callback_url'])) {
                unset($payload['redirect_url']);
            }

            $resp = Http::withToken($flwSecret)
                ->acceptJson()
                ->post('https://api.flutterwave.com/v3/payments', $payload);

            if (!$resp->ok() || ($resp->json('status') !== 'success')) {
                Log::error('Flutterwave initialize failed', ['reference' => $reference, 'body' => $resp->json()]);
                return response()->json([
                    'message' => 'Failed to initialize payment',
                    'errors' => $resp->json('message') ?? 'Unknown error',
                ], 502);
            }

            $data = $resp->json('data');
            return response()->json([
                'authorization_url' => $data['link'] ?? null,
                'checkout_url' => $data['link'] ?? null,
                'reference' => $reference,
                'total' => $totalAmount,
            ]);
        }

        // Default: Paystack
        $secret = config('services.paystack.secret_key');
        if (! $secret) {
            Log::warning('Paystack secret key is not set');
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        $payload = [
            'email' => $user->email,
            'amount' => (int) round($totalAmount * 100), // Kobo
            'reference' => $reference,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user->id,
                'distribution' => $validated['items'],
            ],
        ];
        if (!empty($validated['callback_url'])) {
            $payload['callback_url'] = $validated['callback_url'];
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.paystack.co/transaction/initialize', $payload);

        if (! $response->ok() || ! ($response->json('status') === true)) {
            Log::error('Paystack initialize failed', ['reference' => $reference, 'body' => $response->json()]);
            return response()->json([
                'message' => 'Failed to initialize payment',
                'errors' => $response->json('message') ?? 'Unknown error',
            ], 502);
        }

        $data = $response->json('data');

        return response()->json([
            'authorization_url' => $data['authorization_url'] ?? null,
            'checkout_url' => $data['authorization_url'] ?? null, // backward-compatible alias for frontend
            'access_code' => $data['access_code'] ?? null,
            'reference' => $data['reference'] ?? $reference,
            'total' => $totalAmount,
        ]);
    }
}
