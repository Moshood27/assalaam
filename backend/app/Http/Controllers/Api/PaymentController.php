<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Scheme;
use App\Models\Project;
use App\Models\Contribution;
use App\Services\MonnifyService;
use App\Services\OpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            'items.*.project_id' => 'nullable|integer|exists:projects,id',
            'items.*.savings_group_id' => 'nullable|integer|exists:savings_groups,id',
            'items.*.units' => 'nullable|integer|min:1',
            'items.*.amount' => 'required|numeric|min:1',
            'items.*.category' => 'nullable|string',
            'callback_url' => 'nullable|url',
        ]);

        $user = $request->user();

        // Server-side sanitize/validate each item amount against Scheme rules (never trust frontend amount)
        $schemeIds = collect($validated['items'])->pluck('scheme_id')->unique()->values();
        $schemes = Scheme::whereIn('id', $schemeIds)->get()->keyBy('id');
        // Optional project validation: ensure provided project_id exists and is active
        $projectIds = collect($validated['items'])->pluck('project_id')->filter()->unique()->values();

        // Add project_ids from savings groups if not already in items
        $savingsGroupIds = collect($validated['items'])->pluck('savings_group_id')->filter()->unique()->values();
        $savingsGroups = $savingsGroupIds->isNotEmpty() ? \App\Models\SavingsGroup::whereIn('id', $savingsGroupIds)->get()->keyBy('id') : collect();

        foreach ($validated['items'] as &$item) {
            if (!empty($item['savings_group_id']) && empty($item['project_id'])) {
                $sg = $savingsGroups[$item['savings_group_id']] ?? null;
                if ($sg && $sg->project_id) {
                    $item['project_id'] = $sg->project_id;
                    $projectIds->push($sg->project_id);
                }
            }
        }
        unset($item);

        $projectIds = $projectIds->unique()->values();
        $projects = $projectIds->isNotEmpty() ? Project::whereIn('id', $projectIds)->get()->keyBy('id') : collect();

        $sanitized = [];
        foreach ($validated['items'] as $item) {
            $scheme = $schemes[$item['scheme_id']] ?? null;
            if (! $scheme) continue; // safe-guard; validator already ensures exists
            $amount = round((float) ($item['amount'] ?? 0), 2);
            // Enforce per-scheme minimums
            $min = round((float) ($scheme->min_amount ?? 0), 2);
            if ($amount < max(1, $min)) {
                return response()->json([
                    'message' => 'Amount below minimum for scheme: ' . ($scheme->name ?? ('#'.$scheme->id)),
                ], 422);
            }
            $projectId = $item['project_id'] ?? null;
            $savingsGroupId = $item['savings_group_id'] ?? null;
            $units = (int) ($item['units'] ?? 0);

            if (!empty($projectId)) {
                $p = $projects[$projectId] ?? null;
                if (! $p || !($p->active)) {
                    return response()->json([
                        'message' => 'Selected project is not available',
                    ], 422);
                }

                if ($p->is_unit_based) {
                    if ($units <= 0) {
                        return response()->json([
                            'message' => 'Number of units is required for unit-based project: ' . $p->name,
                        ], 422);
                    }
                    if ($units > $p->available_units) {
                        return response()->json([
                            'message' => "Only {$p->available_units} units available for " . $p->name,
                        ], 422);
                    }
                    // Force the amount to match unit calculation (don't trust frontend)
                    $amount = round($units * (float) $p->unit_price, 2);
                }
            }

            $row = [
                'scheme_id' => (int) $scheme->id,
                'amount' => $amount,
                'units' => $units > 0 ? $units : null,
                'category' => $item['category'] ?? 'deposit',
            ];
            if (!empty($projectId)) {
                $row['project_id'] = (int) $projectId;
            }
            if (!empty($savingsGroupId)) {
                $row['savings_group_id'] = (int) $savingsGroupId;
            }
            $sanitized[] = $row;
        }

        if (empty($sanitized)) {
            return response()->json(['message' => 'No valid items to pay for'], 422);
        }

        $totalAmount = collect($sanitized)->sum(fn ($i) => (float) $i['amount']);
        if ($totalAmount <= 0) {
            return response()->json(['message' => 'Amount must be greater than zero'], 422);
        }

        // Generate unique reference for this payment
        $reference = 'COOP_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        // Pre-create pending contributions for each scheme (idempotent distribution record)
        foreach ($sanitized as $item) {
            $payloadData = [
                'scheme_id' => $item['scheme_id'],
                'amount' => $item['amount'],
                'reference' => $reference,
                'status' => 'pending',
                'category' => $item['category'] ?? 'deposit',
            ];
            if (!empty($item['project_id'])) {
                $payloadData['project_id'] = (int) $item['project_id'];
                $payloadData['units'] = $item['units'] ?? null;
            }
            if (!empty($item['savings_group_id'])) {
                $payloadData['savings_group_id'] = (int) $item['savings_group_id'];
            }
            $user->contributions()->create($payloadData);
        }

        // Choose payment gateway: use primary if not provided
        $defaultGateway = Setting::get('primary_payment_gateway', 'paystack');
        $gateway = strtolower($request->input('gateway', $defaultGateway));

        if (!Setting::get("gateway_{$gateway}_enabled", true)) {
            return response()->json(['message' => "The selected payment gateway ($gateway) is currently disabled. Please try another method."], 422);
        }

        try {
            if ($gateway === 'monnify') {
                $service = app(MonnifyService::class);
                $monnifyData = $service->initializeTransaction([
                    'amount' => round($totalAmount, 2),
                    'customerName' => $user->name,
                    'customerEmail' => $user->email,
                    'paymentReference' => $reference,
                    'paymentDescription' => 'Cooperative payment',
                    'redirectUrl' => $validated['callback_url'] ?? config('app.url'),
                ]);

                if (!$monnifyData) {
                    return response()->json(['message' => 'Failed to initialize Monnify payment'], 502);
                }

                return response()->json([
                    'authorization_url' => $monnifyData['checkoutUrl'] ?? null,
                    'checkout_url' => $monnifyData['checkoutUrl'] ?? null,
                    'reference' => $reference,
                    'total' => $totalAmount,
                ]);
            }

            if ($gateway === 'opay') {
                $service = app(OpayService::class);
                $opayData = $service->initializeTransaction([
                    'amount' => round($totalAmount, 2),
                    'customerName' => $user->name,
                    'customerEmail' => $user->email,
                    'reference' => $reference,
                    'paymentDescription' => 'Cooperative payment',
                    'callbackUrl' => $validated['callback_url'] ?? config('app.url'),
                ]);

                if (!$opayData) {
                    return response()->json(['message' => 'Failed to initialize Opay payment'], 502);
                }

                return response()->json([
                    'authorization_url' => $opayData['cashierUrl'] ?? null,
                    'checkout_url' => $opayData['cashierUrl'] ?? null,
                    'reference' => $reference,
                    'total' => $totalAmount,
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
                        // Include only scheme ids and server-sanctioned amounts
                        'distribution' => $sanitized,
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

                    if (app()->bound('sentry')) {
                        app('sentry')->captureMessage('Payment Failure: Flutterwave Initialize Failed', \Sentry\Severity::error());
                    }

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
                    // Include only scheme ids and server-sanctioned amounts
                    'distribution' => $sanitized,
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

                if (app()->bound('sentry')) {
                    app('sentry')->captureMessage('Payment Failure: Paystack Initialize Failed', \Sentry\Severity::error());
                }

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
        } catch (\Exception $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
            throw $e;
        }
    }

    // Server-side verification endpoint for redirect callbacks (prevents spoofing "success" URLs)
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'gateway' => 'nullable|in:paystack,flutterwave,monnify',
        ]);

        $user = $request->user();
        $reference = trim($validated['reference']);
        $gateway = strtolower($validated['gateway'] ?? 'paystack');

        // Find any contributions for this reference belonging to the authenticated user
        $contributions = Contribution::where('reference', $reference)->get();
        if ($contributions->isNotEmpty()) {
            $ownerId = (int) $contributions->first()->user_id;
            if ($ownerId !== (int) $user->id) {
                // Do not leak info about other users' payments
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        if ($gateway === 'flutterwave' || $gateway === 'monnify') {
            // For now, rely on webhook (already implemented). Return pending status.
            return response()->json(['status' => 'pending', 'message' => 'Awaiting confirmation'], 202);
        }

        // Default: Paystack verify
        $secret = config('services.paystack.secret_key');
        if (! $secret) {
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        $resp = Http::withToken($secret)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(2, 200)
            ->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));

        if (! $resp->ok() || ($resp->json('status') !== true)) {
            Log::warning('Paystack verify call failed (callback)', ['reference' => $reference, 'body' => $resp->json()]);
            return response()->json(['message' => 'Verification failed'], 400);
        }

        $data = $resp->json('data');
        if (! $data || ($data['status'] ?? null) !== 'success') {
            return response()->json(['status' => $data['status'] ?? 'failed'], 200);
        }

        $paidKobo = (int) ($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'NGN';

        // Expected total is from pending or already-success contributions for the reference (belongs to this user)
        $userContribs = Contribution::where('reference', $reference)
            ->where('user_id', $user->id)
            ->get();

        $expectedKobo = (int) round(((float) $userContribs->sum('amount')) * 100);

        if ($currency !== 'NGN' || $paidKobo < $expectedKobo) {
            Log::warning('Paystack verify amount/currency mismatch (callback)', [
                'reference' => $reference,
                'paid_kobo' => $paidKobo,
                'expected_kobo' => $expectedKobo,
                'currency' => $currency,
            ]);
            return response()->json(['message' => 'Amount mismatch'], 400);
        }

        // Mark contributions as success (idempotent)
        foreach ($userContribs as $contribution) {
            if ($contribution->status !== 'success') {
                $contribution->status = 'success';
                $contribution->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'reference' => $reference,
            'amount' => round($expectedKobo / 100, 2),
        ]);
    }
}
