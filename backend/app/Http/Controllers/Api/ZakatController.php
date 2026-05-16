<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\SadaqahProject;
use App\Models\SadaqahContribution;
use App\Models\WalletTransaction;
use App\Models\Setting;
use App\Services\MonnifyService;
use App\Services\OpayService;
use App\Services\ZakatService;
use App\Services\GoldSilverPriceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZakatController extends Controller
{
    protected $priceService;
    protected $zakatService;

    public function __construct(GoldSilverPriceService $priceService, ZakatService $zakatService)
    {
        $this->priceService = $priceService;
        $this->zakatService = $zakatService;
    }

    public function estimate(Request $request)
    {
        $user = $request->user();
        $estimate = $this->zakatService->getEstimate($user);

        return response()->json([
            'base' => $estimate['base'],
            'savings' => $estimate['savings'],
            'shares' => $estimate['shares'],
            'gold_value' => $estimate['gold_value'],
            'wallet_balance' => $estimate['wallet_balance'],
            'nisab' => $estimate['nisab'],
            'rate' => $estimate['rate'],
            'eligible' => $estimate['eligible'],
            'crossed_on' => optional($estimate['crossed_on'])->toDateTimeString(),
            'eligible_on' => optional($estimate['eligible_on'])->toDateTimeString(),
            'days_since_crossed' => $estimate['days_since_crossed'],
            'zakat_due' => $estimate['zakat_due'],
            'is_ramadan' => $estimate['is_ramadan'],
            'fitr_amount' => $estimate['fitr_amount'],
            'last_paid_at' => $estimate['last_paid_at'] ? $estimate['last_paid_at']->toDateTimeString() : null,
        ]);
    }

    public function pay(Request $request)
    {
        $user = $request->user();

        // Compute current base and zakat due using estimate logic
        $estimate = $this->estimate($request)->getData(true);
        if (!is_array($estimate)) {
            return response()->json(['message' => 'Failed to compute Zakat'], 500);
        }

        if (($estimate['base'] ?? 0) < ($estimate['nisab'] ?? (float) config('zakat.nisab_ngn'))) {
            return response()->json(['message' => 'Zakat is not due yet (below Nisab)'], 422);
        }

        $amount = round(($estimate['zakat_due'] ?? 0), 2);
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid Zakat amount'], 422);
        }

        // Handle Internal Wallet Payment
        $gateway = strtolower($request->input('gateway', 'paystack'));

        if ($gateway !== 'wallet' && !Setting::get("gateway_{$gateway}_enabled", true)) {
            return response()->json(['message' => "The selected payment gateway ($gateway) is currently disabled. Please try another method."], 422);
        }

        if ($gateway === 'wallet') {
            if (!$user->verifyTransactionPin($request->input('pin'))) {
                return response()->json(['message' => 'Invalid transaction PIN'], 403);
            }
            return $this->payInternal($user, $amount, 'Zakat');
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

        if ($gateway === 'monnify') {
            $service = app(MonnifyService::class);
            $monnifyData = $service->initializeTransaction([
                'amount' => round($amount, 2),
                'customerName' => $user->name,
                'customerEmail' => $user->email,
                'paymentReference' => $reference,
                'paymentDescription' => 'Zakat payment',
                'redirectUrl' => $request->input('callback_url') ?? config('app.url'),
            ]);

            if (!$monnifyData) {
                return response()->json(['message' => 'Failed to initialize Monnify payment'], 502);
            }

            return response()->json([
                'authorization_url' => $monnifyData['checkoutUrl'] ?? null,
                'checkout_url' => $monnifyData['checkoutUrl'] ?? null,
                'reference' => $reference,
                'total' => $amount,
            ]);
        }

        if ($gateway === 'opay') {
            $service = app(OpayService::class);
            $opayData = $service->initializeTransaction([
                'amount' => round($amount, 2),
                'customerName' => $user->name,
                'customerEmail' => $user->email,
                'reference' => $reference,
                'paymentDescription' => 'Zakat payment',
                'callbackUrl' => $request->input('callback_url') ?? config('app.url'),
            ]);

            if (!$opayData) {
                return response()->json(['message' => 'Failed to initialize Opay payment'], 502);
            }

            return response()->json([
                'authorization_url' => $opayData['cashierUrl'] ?? null,
                'checkout_url' => $opayData['cashierUrl'] ?? null,
                'reference' => $reference,
                'total' => $amount,
            ]);
        }

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

    public function payFitr(Request $request)
    {
        $user = $request->user();
        $amount = (float) config('zakat.fitr_amount');

        if ($amount <= 0) {
            return response()->json(['message' => 'Zakat Al-Fitr amount is not configured'], 422);
        }

        // Handle Internal Wallet Payment
        $gateway = strtolower($request->input('gateway', 'paystack'));

        if ($gateway !== 'wallet' && !Setting::get("gateway_{$gateway}_enabled", true)) {
            return response()->json(['message' => "The selected payment gateway ($gateway) is currently disabled. Please try another method."], 422);
        }

        if ($gateway === 'wallet') {
            if (!$user->verifyTransactionPin($request->input('pin'))) {
                return response()->json(['message' => 'Invalid transaction PIN'], 403);
            }
            return $this->payInternal($user, $amount, 'Zakat Al-Fitr');
        }

        // Ensure a Zakat Al-Fitr scheme exists
        $scheme = Scheme::firstOrCreate(
            ['name' => 'Zakat Al-Fitr'],
            ['min_amount' => 1, 'active' => true]
        );

        $reference = 'FITR_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        $user->contributions()->create([
            'scheme_id' => $scheme->id,
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        if ($gateway === 'monnify') {
            $service = app(MonnifyService::class);
            $monnifyData = $service->initializeTransaction([
                'amount' => round($amount, 2),
                'customerName' => $user->name,
                'customerEmail' => $user->email,
                'paymentReference' => $reference,
                'paymentDescription' => 'Zakat Fitr payment',
                'redirectUrl' => $request->input('callback_url') ?? config('app.url'),
            ]);

            if (!$monnifyData) {
                return response()->json(['message' => 'Failed to initialize Monnify payment'], 502);
            }

            return response()->json([
                'authorization_url' => $monnifyData['checkoutUrl'] ?? null,
                'checkout_url' => $monnifyData['checkoutUrl'] ?? null,
                'reference' => $reference,
                'total' => $amount,
            ]);
        }

        if ($gateway === 'opay') {
            $service = app(OpayService::class);
            $opayData = $service->initializeTransaction([
                'amount' => round($amount, 2),
                'customerName' => $user->name,
                'customerEmail' => $user->email,
                'reference' => $reference,
                'paymentDescription' => 'Zakat Fitr payment',
                'callbackUrl' => $request->input('callback_url') ?? config('app.url'),
            ]);

            if (!$opayData) {
                return response()->json(['message' => 'Failed to initialize Opay payment'], 502);
            }

            return response()->json([
                'authorization_url' => $opayData['cashierUrl'] ?? null,
                'checkout_url' => $opayData['cashierUrl'] ?? null,
                'reference' => $reference,
                'total' => $amount,
            ]);
        }

        if ($gateway === 'flutterwave') {
            $flwSecret = config('services.flutterwave.secret_key');
            $payload = [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => 'NGN',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'meta' => ['user_id' => $user->id, 'fitr' => true],
            ];

            $resp = Http::withToken($flwSecret)->post('https://api.flutterwave.com/v3/payments', $payload);
            if (!$resp->ok()) {
                return response()->json(['message' => 'Payment initialization failed'], 502);
            }
            return response()->json([
                'checkout_url' => $resp->json('data.link'),
                'reference' => $reference,
                'total' => $amount,
            ]);
        }

        $secret = config('services.paystack.secret_key');
        $payload = [
            'email' => $user->email,
            'amount' => (int)($amount * 100),
            'reference' => $reference,
            'metadata' => ['user_id' => $user->id, 'fitr' => true],
        ];

        $response = Http::withToken($secret)->post('https://api.paystack.co/transaction/initialize', $payload);
        if (!$response->ok()) {
            return response()->json(['message' => 'Payment initialization failed'], 502);
        }

        return response()->json([
            'checkout_url' => $response->json('data.authorization_url'),
            'reference' => $reference,
            'total' => $amount,
        ]);
    }

    protected function payInternal($user, $amount, $type = 'Zakat')
    {
        if ($user->balance < $amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        try {
            DB::transaction(function () use ($user, $amount, $type) {
                // Deduct from wallet
                $user->balance -= $amount;
                $user->save();

                // Create Wallet Transaction for deduction
                $user->walletTransactions()->create([
                    'amount' => -$amount,
                    'type' => 'debit',
                    'category' => 'contribution',
                    'description' => "Payment for {$type}",
                    'reference' => strtoupper($type) . '_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(2)),
                    'status' => 'success',
                ]);

                // Create Contribution record
                $scheme = Scheme::firstOrCreate(['name' => $type], ['active' => true]);
                $user->contributions()->create([
                    'scheme_id' => $scheme->id,
                    'amount' => $amount,
                    'status' => 'success',
                    'reference' => 'INTERNAL_' . strtoupper($type) . '_' . now()->format('YmdHis'),
                ]);

                // Move to Zakat Fund (SadaqahProject)
                $zakatProject = SadaqahProject::firstOrCreate(
                    ['name' => 'General Zakat Fund'],
                    ['description' => 'Automated Zakat Fund', 'active' => true]
                );

                SadaqahContribution::create([
                    'user_id' => $user->id,
                    'sadaqah_project_id' => $zakatProject->id,
                    'amount' => $amount,
                    'status' => 'success',
                    'reference' => 'ZAKAT_FUND_MOVE_' . now()->format('YmdHis'),
                ]);

                $zakatProject->increment('raised_amount', $amount);

                if ($type === 'Zakat') {
                    $user->update([
                        'zakat_last_paid_at' => now(),
                        'zakat_nisab_crossed_at' => now(), // Start next Hawl cycle if already above nisab
                    ]);
                }
            });

            return response()->json(['message' => "{$type} paid successfully using wallet balance"]);
        } catch (\Exception $e) {
            Log::error("Internal {$type} payment failed: " . $e->getMessage());
            return response()->json(['message' => 'Internal transfer failed. Please try again.'], 500);
        }
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $zakatProject = SadaqahProject::where('name', 'General Zakat Fund')->first();

        if (!$zakatProject) {
            return response()->json([]);
        }

        $history = SadaqahContribution::where('user_id', $user->id)
            ->where('sadaqah_project_id', $zakatProject->id)
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($history);
    }
}
