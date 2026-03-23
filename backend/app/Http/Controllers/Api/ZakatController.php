<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZakatController extends Controller
{
    public function estimate(Request $request)
    {
        $user = $request->user();

        // Resolve scheme IDs for Savings and Shares
        $schemes = Scheme::whereIn('name', ['Savings', 'Shares'])->pluck('id', 'name');

        // Compute current balances from contributions with status success
        $savings = 0.0;
        $shares = 0.0;
        if (isset($schemes['Savings'])) {
            $savings = (float) $user->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Savings'])
                ->sum('amount');
        }
        if (isset($schemes['Shares'])) {
            $shares = (float) $user->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Shares'])
                ->sum('amount');
        }

        $base = round($savings + $shares, 2);

        $nisab = (float) config('zakat.nisab_ngn');
        $rate = (float) config('zakat.rate');
        $lunarDays = (int) config('zakat.lunar_days');

        $eligible = false;
        $crossedOn = null;
        $eligibleOn = null;
        $daysSinceCrossed = 0;

        if ($base >= $nisab && ($schemes->count() > 0)) {
            // Find earliest date when cumulative savings+shares crossed nisab
            $contribs = $user->contributions()
                ->where('status', 'success')
                ->whereIn('scheme_id', array_values($schemes->toArray()))
                ->orderBy('created_at', 'asc')
                ->get(['amount', 'created_at']);

            $running = 0.0;
            foreach ($contribs as $c) {
                $running += (float) $c->amount;
                if ($running >= $nisab) {
                    $crossedOn = $c->created_at?->copy();
                    break;
                }
            }

            if ($crossedOn) {
                $eligibleOn = $crossedOn->copy()->addDays($lunarDays);
                $daysSinceCrossed = now()->diffInDays($crossedOn);
                $eligible = now()->greaterThanOrEqualTo($eligibleOn);
            }
        }

        $zakatDue = round($base * $rate, 2);

        return response()->json([
            'base' => $base,
            'savings' => $savings,
            'shares' => $shares,
            'nisab' => $nisab,
            'rate' => $rate,
            'eligible' => $eligible,
            'crossed_on' => optional($crossedOn)->toDateTimeString(),
            'eligible_on' => optional($eligibleOn)->toDateTimeString(),
            'days_since_crossed' => $daysSinceCrossed,
            'zakat_due' => $zakatDue,
        ]);
    }

    public function pay(Request $request)
    {
        $user = $request->user();

        // Compute current base and zakat due using estimate logic (without extra queries if possible)
        $estimate = $this->estimate($request)->getData(true);
        if (!is_array($estimate)) {
            return response()->json(['message' => 'Failed to compute Zakat'], 500);
        }

        if (($estimate['base'] ?? 0) < (float) config('zakat.nisab_ngn')) {
            return response()->json(['message' => 'Zakat is not due yet (below Nisab)'], 422);
        }

        $amount = round(($estimate['zakat_due'] ?? 0), 2);
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid Zakat amount'], 422);
        }

        // Ensure a Zakat scheme exists
        $zakatScheme = Scheme::firstOrCreate(
            ['name' => 'Zakat'],
            ['min_amount' => 1, 'active' => true]
        );

        // Create a reference shared for this transaction
        $reference = 'ZAKAT_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        // Pre-create a pending contribution for record/idempotency
        $user->contributions()->create([
            'scheme_id' => $zakatScheme->id,
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        // Choose payment gateway
        $gateway = strtolower($request->input('gateway', 'paystack'));

        if ($gateway === 'flutterwave') {
            $flwSecret = config('services.flutterwave.secret_key');
            if (!$flwSecret) {
                Log::warning('Flutterwave secret key is not set');
                return response()->json(['message' => 'Payment provider not configured'], 500);
            }

            $payload = [
                'tx_ref' => $reference,
                'amount' => round($amount, 2),
                'currency' => 'NGN',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phonenumber' => $user->phone,
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'zakat' => true,
                ],
            ];

            $resp = Http::withToken($flwSecret)
                ->acceptJson()
                ->post('https://api.flutterwave.com/v3/payments', $payload);

            if (!$resp->ok() || ($resp->json('status') !== 'success')) {
                Log::error('Flutterwave Zakat initialize failed', ['reference' => $reference, 'body' => $resp->json()]);
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
                'total' => $amount,
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
            'amount' => (int) round($amount * 100), // Kobo
            'reference' => $reference,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user->id,
                'zakat' => true,
            ],
        ];

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.paystack.co/transaction/initialize', $payload);

        if (! $response->ok() || ! ($response->json('status') === true)) {
            Log::error('Paystack Zakat initialize failed', ['reference' => $reference, 'body' => $response->json()]);
            return response()->json([
                'message' => 'Failed to initialize payment',
                'errors' => $response->json('message') ?? 'Unknown error',
            ], 502);
        }

        $data = $response->json('data');

        return response()->json([
            'authorization_url' => $data['authorization_url'] ?? null,
            'checkout_url' => $data['authorization_url'] ?? null,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $data['reference'] ?? $reference,
            'total' => $amount,
        ]);
    }
}
