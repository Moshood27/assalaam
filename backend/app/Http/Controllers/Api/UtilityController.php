<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UtilityTransaction;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UtilityController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log entire webhook for diagnostics
        Log::info('VTpass Webhook Received', ['payload' => $request->all()]);

        // Extract request_id/reference from common fields
        $payload = $request->all();
        // Try our own cb_ref first (we append ?ref=reference in callback URLs)
        $cbRef = $request->query('ref') ?? ($payload['ref'] ?? null);
        $reference = $payload['request_id']
            ?? ($payload['requestId'] ?? ($payload['reference'] ?? ($payload['data']['requestId'] ?? ($payload['content']['transactions']['requestId'] ?? $cbRef))));

        if (!$reference) {
            return response()->json(['status' => 'received', 'note' => 'missing reference'], 200);
        }

        $tx = UtilityTransaction::where('reference', $reference)->first();
        if (!$tx) {
            Log::warning('VTpass Webhook: Transaction not found for reference', ['reference' => $reference]);
            return response()->json(['status' => 'received', 'note' => 'unknown reference'], 200);
        }

        // Determine status using existing helpers
        $status = 'failed';
        if ($this->isVtpassSuccess($payload)) {
            $status = 'success';
        } elseif ($this->isVtpassPending($payload)) {
            $status = 'pending';
        }

        // Idempotent updates inside DB transaction
        DB::transaction(function () use ($tx, $status, $payload) {
            $user = $tx->user()->lockForUpdate()->first();

            // Always persist provider response
            $tx->provider_response = $payload;

            if ($status === 'success') {
                // If not already marked success, finalize and ensure wallet is debited once
                if ($tx->status !== 'success') {
                    // Ensure profit is computed
                    $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                    $tx->status = 'success';
                    $tx->profit = $profit;

                    // Check if a debit exists for this reference
                    $hasDebit = WalletTransaction::where('reference', $tx->reference)
                        ->where('type', 'debit')
                        ->exists();

                    if (!$hasDebit) {
                        // Debit wallet once
                        $debitAmount = (float) $tx->amount; // airtime uses amount; data already includes convenience
                        $user->decrement('balance', $debitAmount);

                        WalletTransaction::create([
                            'user_id' => $user->id,
                            'type' => 'debit',
                            'amount' => $debitAmount,
                            'reference' => $tx->reference,
                            'source' => match ($tx->type) {
                                'airtime' => 'vtu_airtime',
                                'data' => 'vtu_data',
                                'electricity' => 'vtu_electricity',
                                'cable' => 'vtu_cable',
                                default => 'vtu_other',
                            },
                            'meta' => [
                                'network' => $tx->network,
                                'phone_number' => $tx->phone_number,
                                'utility_tx_id' => $tx->id,
                                'webhook' => true,
                            ],
                        ]);
                    }
                }
            } elseif ($status === 'failed') {
                // Mark failed and refund if previously debited
                $tx->status = 'failed';

                $hasDebit = WalletTransaction::where('reference', $tx->reference)
                    ->where('type', 'debit')
                    ->exists();

                if ($hasDebit) {
                    $refundRef = $tx->reference . '-REFUND';
                    $hasRefund = WalletTransaction::where('reference', $refundRef)
                        ->where('type', 'credit')
                        ->exists();
                    if (!$hasRefund) {
                        $refundAmount = (float) $tx->amount;
                        $user->increment('balance', $refundAmount);
                        WalletTransaction::create([
                            'user_id' => $user->id,
                            'type' => 'credit',
                            'amount' => $refundAmount,
                            'reference' => $refundRef,
                            'source' => 'vtu_refund',
                            'meta' => [
                                'utility_tx_id' => $tx->id,
                                'original_reference' => $tx->reference,
                                'webhook' => true,
                            ],
                        ]);
                    }
                }
            } else {
                // Pending: just update provider response and leave status as pending
                $tx->status = 'pending';
            }

            $tx->save();
        });

        return response()->json(['status' => 'received'], 200);
    }

    public function transactions(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|in:airtime,data,electricity,cable',
            'status' => 'nullable|in:pending,success,failed',
        ]);
        $user = $request->user();
        $perPage = $validated['per_page'] ?? 15;

        // If table doesn't exist yet (fresh env), return empty paginator shape
        if (!Schema::hasTable('utility_transactions')) {
            return response()->json($this->emptyPage($validated['page'] ?? 1, $perPage));
        }

        $query = UtilityTransaction::where('user_id', $user->id)->latest();
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        return response()->json($query->paginate($perPage));
    }

    public function purchaseAirtime(Request $request)
    {
        $validated = $request->validate([
            'network' => 'required|in:mtn,airtel,glo,9mobile,etisalat',
            'phone_number' => 'required|string|min:10|max:15',
            'amount' => 'required|numeric|min:50',
            'reference' => 'nullable|string|max:100',
            'bonus_type' => 'nullable|string|max:5',
            'pin' => ['required','regex:/^\d{4}$/'],
        ]);

        $user = $request->user();
        // Enforce Transaction PIN
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }
        $amount = (float)$validated['amount'];
        if ((float)$user->balance < $amount) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = $validated['reference'] ?? $this->generateReference('AIRTIME', $user->id);
        $reference = $this->ensureVtpassReference($reference);
        if (UtilityTransaction::where('reference', $reference)->exists()) {
            return response()->json(['message' => 'Duplicate reference'], 409);
        }

        $network = $this->normalizeNetwork($validated['network']);
        $serviceId = $this->airtimeServiceId($network);
        $phone = $this->normalizeMsisdn($validated['phone_number']);

        $discount = (float) (config('services.vtu.default_discount', 0.03));
        $costPrice = round($amount * (1 - max(0, min(1, $discount))), 2);

        $tx = UtilityTransaction::create([
            'user_id' => $user->id,
            'type' => 'airtime',
            'network' => $network,
            'phone_number' => $phone,
            'amount' => $amount,
            'cost_price' => $costPrice,
            'profit' => 0,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $payload = [
            'request_id' => $reference,
            'serviceID' => $serviceId,
            'amount' => $amount,
            'phone' => $phone,
        ];
        if (!empty($validated['bonus_type'] ?? null)) {
            $payload['bonus_type'] = $validated['bonus_type'];
        }

        // Provide per-request callback URL to ensure webhook delivery even if dashboard isn't configured
        $callbackUrl = trim((string) config('services.vtu.webhook_url'));
        if (!empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = $this->callVtuSmart('airtime', $payload);
        $providerUsed = $response['provider_used'] ?? 'vtpass';

        if (!$response['ok']) {
            $tx->update([
                'status' => 'failed',
                'provider_response' => $response['body'],
            ]);
            $status = isset($response['status']) ? (int)$response['status'] : null;
            $httpStatus = 502;
            if ($status !== null && $status >= 400 && $status < 500) {
                // Map provider client errors to 400 to avoid triggering frontend 401 auto-logout
                $httpStatus = 400;
            }
            return response()->json([
                'message' => 'Failed to process airtime purchase',
                'errors' => $response['error'] ?? 'Provider error',
                'provider' => $response['body'] ?? null,
                'reference' => $reference,
            ], $httpStatus);
        }

        $body = $response['body'];
        $success = $this->isVtpassSuccess($body);

        if (!$success) {
            // If provider indicates pending/processing, perform a single requery before deciding
            if ($this->isVtpassPending($body)) {
                if (($providerUsed ?? 'vtpass') === 'vtpass') {
                    $requery = $this->requeryVtpass($reference);
                    if ($requery['ok']) {
                        $rb = $requery['body'];
                        if ($this->isVtpassSuccess($rb)) {
                            // Treat as success below (continue to debit)
                            $body = $rb;
                        } elseif ($this->isVtpassPending($rb)) {
                            // Keep transaction pending and inform client
                            $tx->update([
                                'status' => 'pending',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Airtime is processing with provider. Check history for final status shortly.',
                                'status' => 'pending',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 200);
                        } else {
                            // Definitive failure after requery
                            $tx->update([
                                'status' => 'failed',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Airtime purchase failed',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 400);
                        }
                    } else {
                        // Requery failed (network or 4xx); keep as pending and let client retry/view history
                        $tx->update([
                            'status' => 'pending',
                            'provider_response' => $body,
                        ]);
                        return response()->json([
                            'message' => 'Airtime is processing. Unable to confirm now; please check history soon.',
                            'status' => 'pending',
                            'provider' => $requery['body'] ?? $body,
                            'reference' => $reference,
                        ], 200);
                    }
                } else {
                    // For non-VTpass providers, do not requery here; allow webhook or later reconciliation
                    $tx->update([
                        'status' => 'pending',
                        'provider_response' => $body,
                    ]);
                    return response()->json([
                        'message' => 'Airtime is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
            } else {
                // Unknown/ambiguous provider state. In Sandbox, VTpass can still deliver later
                // even if the immediate response isn't clearly marked pending. To avoid false
                // failures in development/testing, treat this as pending when sandbox is enabled.
                $isSandbox = (bool) config('services.vtu.sandbox');
                $tx->update([
                    'status' => $isSandbox ? 'pending' : 'failed',
                    'provider_response' => $body,
                ]);
                if ($isSandbox) {
                    return response()->json([
                        'message' => 'Airtime is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
                return response()->json([
                    'message' => 'Airtime purchase failed',
                    'provider' => $body,
                    'reference' => $reference,
                ], 400);
            }
        }

        $insufficient = false;
        DB::transaction(function () use ($user, $amount, $reference, $tx, $body, &$insufficient) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
            if ((float)$lockedUser->balance < (float)$amount) {
                // Not enough funds at debit time; leave pending and save provider response
                $tx->update([
                    'status' => 'pending',
                    'provider_response' => $body,
                ]);
                $insufficient = true;
                return;
            }

            // Deduct wallet balance safely
            $lockedUser->decrement('balance', $amount);

            // Profit = amount - cost_price (pre-set)
            $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);

            // Update tx status and profit
            $tx->update([
                'status' => 'success',
                'profit' => $profit,
                'provider_response' => $body,
            ]);

            // Record wallet debit transaction
            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'source' => 'vtu_airtime',
                'meta' => [
                    'network' => $tx->network,
                    'phone_number' => $tx->phone_number,
                    'utility_tx_id' => $tx->id,
                ],
            ]);
        });

        if ($insufficient) {
            $user->refresh();
            return response()->json([
                'message' => 'Airtime is processing. Wallet will be debited when funds are available.',
                'status' => 'pending',
                'reference' => $reference,
                'balance' => (float)$user->balance,
                'transaction' => $tx->fresh(),
            ], 202);
        }

        $user->refresh();

        // Best-effort SMS notification
        try {
            $sms = app(\App\Services\SmsService::class);
            $msg = 'Airtime purchased: ₦'.number_format($amount, 2).' for '.($tx->phone_number).'. Ref: '.$reference.'. Bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        return response()->json([
            'message' => 'Airtime sent!',
            'status' => 'success',
            'reference' => $reference,
            'balance' => (float)$user->balance,
            'transaction' => $tx->fresh(),
        ]);
    }

    public function purchaseData(Request $request)
    {
        $validated = $request->validate([
            'network' => 'required|in:mtn,airtel,glo,9mobile,etisalat',
            'phone_number' => 'required|string|min:10|max:15',
            'bundle_code' => 'required|string', // Provider variation code
            'amount' => 'required|numeric|min:50',
            'reference' => 'nullable|string|max:100',
            'pin' => ['required','regex:/^\d{4}$/'],
        ]);

        $user = $request->user();
        // Enforce Transaction PIN
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }
        $amount = (float)$validated['amount'];
        $convenience = (float) (config('services.vtu.convenience_fee', 0));
        if ((float)$user->balance < ($amount + $convenience)) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = $validated['reference'] ?? $this->generateReference('DATA', $user->id);
        $reference = $this->ensureVtpassReference($reference);
        if (UtilityTransaction::where('reference', $reference)->exists()) {
            return response()->json(['message' => 'Duplicate reference'], 409);
        }

        $network = $this->normalizeNetwork($validated['network']);
        $serviceId = $this->dataServiceId($network);
        $phone = $this->normalizeMsisdn($validated['phone_number']);

        $discount = (float) (config('services.vtu.default_discount', 0.03));
        $costPrice = round($amount * (1 - max(0, min(1, $discount))), 2);

        $tx = UtilityTransaction::create([
            'user_id' => $user->id,
            'type' => 'data',
            'network' => $network,
            'phone_number' => $phone,
            'amount' => $amount + $convenience,
            'cost_price' => $costPrice,
            'profit' => 0,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $payload = [
            'request_id' => $reference,
            'serviceID' => $serviceId,
            'billersCode' => $phone,
            'variation_code' => $validated['bundle_code'],
            'amount' => $amount, // base amount without convenience
            'phone' => $phone,
        ];

        // Provide per-request callback URL to ensure webhook delivery even if dashboard isn't configured
        $callbackUrl = trim((string) config('services.vtu.webhook_url'));
        if (!empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        $bundleCode = $validated['bundle_code'];
        $response = $this->callVtuSmart('data', $payload);
        $providerUsed = $response['provider_used'] ?? 'vtpass';

        if (!$response['ok']) {
            $tx->update([
                'status' => 'failed',
                'provider_response' => $response['body'],
            ]);
            $status = isset($response['status']) ? (int)$response['status'] : null;
            $httpStatus = 502;
            if ($status !== null && $status >= 400 && $status < 500) {
                $httpStatus = 400;
            }
            return response()->json([
                'message' => 'Failed to process data purchase',
                'errors' => $response['error'] ?? 'Provider error',
                'provider' => $response['body'] ?? null,
                'reference' => $reference,
            ], $httpStatus);
        }

        $body = $response['body'];
        $success = $this->isVtpassSuccess($body);

        if (!$success) {
            // If provider indicates pending/processing, perform a single requery before deciding
            if ($this->isVtpassPending($body)) {
                if (($providerUsed ?? 'vtpass') === 'vtpass') {
                    $requery = $this->requeryVtpass($reference);
                    if ($requery['ok']) {
                        $rb = $requery['body'];
                        if ($this->isVtpassSuccess($rb)) {
                            // Treat as success below (continue to debit)
                            $body = $rb;
                        } elseif ($this->isVtpassPending($rb)) {
                            // Keep transaction pending and inform client
                            $tx->update([
                                'status' => 'pending',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Data purchase is processing with provider. Check history for final status shortly.',
                                'status' => 'pending',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 200);
                        } else {
                            // Definitive failure after requery
                            $tx->update([
                                'status' => 'failed',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Data purchase failed',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 400);
                        }
                    } else {
                        // Requery failed (network or 4xx); keep as pending and let client retry/view history
                        $tx->update([
                            'status' => 'pending',
                            'provider_response' => $body,
                        ]);
                        return response()->json([
                            'message' => 'Data purchase is processing. Unable to confirm now; please check history soon.',
                            'status' => 'pending',
                            'provider' => $requery['body'] ?? $body,
                            'reference' => $reference,
                        ], 200);
                    }
                } else {
                    // For non-VTpass providers, do not requery here; allow webhook or later reconciliation
                    $tx->update([
                        'status' => 'pending',
                        'provider_response' => $body,
                    ]);
                    return response()->json([
                        'message' => 'Data purchase is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
            } else {
                // Unknown/ambiguous provider state. In Sandbox, VTpass can still deliver later
                // even if the immediate response isn't clearly marked pending. To avoid false
                // failures in development/testing, treat this as pending when sandbox is enabled.
                $isSandbox = (bool) config('services.vtu.sandbox');
                $tx->update([
                    'status' => $isSandbox ? 'pending' : 'failed',
                    'provider_response' => $body,
                ]);
                if ($isSandbox) {
                    return response()->json([
                        'message' => 'Data purchase is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
                return response()->json([
                    'message' => 'Data purchase failed',
                    'provider' => $body,
                    'reference' => $reference,
                ], 400);
            }
        }

        $insufficient2 = false;
        DB::transaction(function () use ($user, $amount, $reference, $tx, $body, $convenience, $bundleCode, &$insufficient2) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
            $debit = round($amount + $convenience, 2);
            if ((float)$lockedUser->balance < (float)$debit) {
                $tx->update([
                    'status' => 'pending',
                    'provider_response' => $body,
                ]);
                $insufficient2 = true;
                return;
            }

            $lockedUser->decrement('balance', $debit);

            $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);

            $tx->update([
                'status' => 'success',
                'profit' => $profit,
                'provider_response' => $body,
            ]);

            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $debit,
                'reference' => $reference,
                'source' => 'vtu_data',
                'meta' => [
                    'network' => $tx->network,
                    'phone_number' => $tx->phone_number,
                    'bundle_code' => $bundleCode,
                    'utility_tx_id' => $tx->id,
                    'convenience_fee' => $convenience,
                ],
            ]);
        });

        if ($insufficient2) {
            $user->refresh();
            return response()->json([
                'message' => 'Data purchase is processing. Wallet will be debited when funds are available.',
                'status' => 'pending',
                'reference' => $reference,
                'balance' => (float)$user->balance,
                'transaction' => $tx->fresh(),
            ], 202);
        }

        $user->refresh();

        // Best-effort SMS notification
        try {
            $sms = app(\App\Services\SmsService::class);
            $debit = round((float) $tx->amount, 2); // includes convenience fee
            $msg = 'Data purchased: ₦'.number_format($debit, 2).' '.strtoupper($tx->network).' ('.($bundleCode).') for '.($tx->phone_number).'. Ref: '.$reference.'. Bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        return response()->json([
            'message' => 'Data purchased!',
            'status' => 'success',
            'reference' => $reference,
            'balance' => (float)$user->balance,
            'transaction' => $tx->fresh(),
        ]);
    }

    public function dataBundles(Request $request)
    {
        $validated = $request->validate([
            'network' => 'required|in:mtn,airtel,glo,9mobile,etisalat',
        ]);

        $network = $this->normalizeNetwork($validated['network']);
        $serviceId = $this->dataServiceId($network);

        $baseUrl = rtrim(config('services.vtu.base_url', 'https://vtpass.com/api'), '/');
        $apiKey = config('services.vtu.api_key');
        $publicKey = config('services.vtu.public_key');
        $secretKey = config('services.vtu.secret_key');

        $cacheKey = 'vtu:data:variations:' . $serviceId;
        $ttl = now()->addMinutes(30);

        $headers = [ 'api-key' => $apiKey ];
        if ($publicKey) { $headers['public-key'] = $publicKey; }
        if ($secretKey) { $headers['secret-key'] = $secretKey; }

        $convenience = (float) (config('services.vtu.convenience_fee', 0));

        // If VTpass keys are not set, try ClubKonnect (Nellobytes) as a fallback source for plans; else serve cached/static
        if (!$apiKey || (!$publicKey && !$secretKey)) {
            $ck = config('services.vtu.clubkonnect', []);
            $ckEnabled = (bool)($ck['enabled'] ?? false);
            $ckUser = $ck['user_id'] ?? null;
            $ckKey = $ck['api_key'] ?? null;
            $ckBase = rtrim((string)($ck['base_url'] ?? 'https://www.nellobytesystems.com'), '/');
            if ($ckEnabled && $ckUser && $ckKey) {
                try {
                    $r = Http::timeout(10)
                        ->acceptJson()
                        ->get($ckBase . '/APIDatabundlePlansV2.asp', [ 'UserID' => $ckUser ]);
                    $j = $r->json();
                    if ($r->ok() && is_array($j)) {
                        // Attempt to locate the array for the requested network
                        $keyMap = [ 'mtn' => ['MTN','mtn'], 'glo' => ['Glo','glo'], 'airtel' => ['Airtel','airtel'], '9mobile' => ['9mobile','etisalat','9MOBILE'] ];
                        $sections = $keyMap[$network] ?? [];
                        $plansRaw = null;
                        foreach ($sections as $k) {
                            if (isset($j[$k]) && is_array($j[$k])) { $plansRaw = $j[$k]; break; }
                            if (isset($j['data'][$k]) && is_array($j['data'][$k])) { $plansRaw = $j['data'][$k]; break; }
                            if (isset($j['plans'][$k]) && is_array($j['plans'][$k])) { $plansRaw = $j['plans'][$k]; break; }
                        }
                        // Fallback: if top-level is a list, filter by network label inside
                        if ($plansRaw === null && isset($j['data']) && is_array($j['data'])) {
                            $plansRaw = $j['data'];
                        }
                        if ($plansRaw === null && isset($j['plans']) && is_array($j['plans'])) {
                            $plansRaw = $j['plans'];
                        }
                        if ($plansRaw === null && array_is_list($j)) {
                            $plansRaw = $j;
                        }

                        $bundles = [];
                        if (is_array($plansRaw)) {
                            foreach ($plansRaw as $p) {
                                // Optionally filter if items contain a network field
                                $pn = strtolower((string)($p['network'] ?? $p['provider'] ?? ''));
                                if ($pn && $pn !== $network) { continue; }
                                $code = (string)($p['dataplan_id'] ?? ($p['id'] ?? ($p['code'] ?? '')));
                                $name = (string)($p['name'] ?? ($p['plan'] ?? ($p['description'] ?? '')));
                                $amount = (float)($p['amount'] ?? ($p['price'] ?? ($p['cost'] ?? 0)));
                                if ($code === '' || $amount <= 0) { continue; }
                                $bundles[] = [
                                    'code' => $code,
                                    'name' => $name,
                                    'amount' => $amount,
                                    'fixed' => true,
                                    'convenience_fee' => $convenience,
                                    'total_debit' => round($amount + $convenience, 2),
                                ];
                            }
                        }

                        if (!empty($bundles)) {
                            $payload = [ 'bundles' => $bundles, 'provider_response' => $j ];
                            \Illuminate\Support\Facades\Cache::put($cacheKey, $payload, $ttl);
                            return response()->json([
                                'network' => $network,
                                'service_id' => $serviceId,
                                'convenience_fee' => $convenience,
                                'bundles' => $bundles,
                                'provider_response' => $j,
                                'stale' => false,
                                'note' => 'Fetched from ClubKonnect',
                            ], 200);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('ClubKonnect data plans fetch failed', ['error' => $e->getMessage()]);
                }
            }

            // Fallback to cached/static if CK not configured or failed
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            $resp = [
                'network' => $network,
                'service_id' => $serviceId,
                'convenience_fee' => $convenience,
                'bundles' => $cached['bundles'] ?? [],
                'provider_response' => $cached['provider_response'] ?? null,
                'stale' => true,
                'note' => 'Provider not configured; serving cached/static bundles',
            ];
            return response()->json($resp, 200);
        }

        try {
            $resp = Http::withHeaders($headers)
                ->timeout(8)
                ->retry(1, 200)
                ->acceptJson()
                ->get($baseUrl . '/service-variations', [ 'serviceID' => $serviceId ]);
        } catch (\Throwable $e) {
            Log::error('VTU variations HTTP error', ['exception' => $e->getMessage()]);
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached) {
                $cached['stale'] = true;
                $cached['note'] = 'Network error; serving cached bundles';
                return response()->json(array_merge([
                    'network' => $network,
                    'service_id' => $serviceId,
                    'convenience_fee' => $convenience,
                ], $cached), 200);
            }
            return response()->json([
                'network' => $network,
                'service_id' => $serviceId,
                'convenience_fee' => $convenience,
                'bundles' => [],
                'note' => 'Network error; no cached bundles available',
            ], 200);
        }

        $json = $resp->json();
        if (!$resp->ok()) {
            Log::error('VTU variations bad response', ['status' => $resp->status(), 'body' => $json]);
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached) {
                $cached['stale'] = true;
                $cached['note'] = 'Provider error; serving cached bundles';
                return response()->json(array_merge([
                    'network' => $network,
                    'service_id' => $serviceId,
                    'convenience_fee' => $convenience,
                ], $cached), 200);
            }
            return response()->json([
                'network' => $network,
                'service_id' => $serviceId,
                'convenience_fee' => $convenience,
                'bundles' => [],
                'note' => 'Failed to fetch bundles; no cached bundles available',
                'provider_status' => $resp->status(),
            ], 200);
        }

        $raw = $json['content']['varations'] ?? $json['content']['variations'] ?? $json['data']['variations'] ?? [];
        $bundles = array_values(array_map(function ($v) use ($convenience) {
            $code = $v['variation_code'] ?? ($v['code'] ?? null);
            $name = $v['name'] ?? '';
            $amount = (float) ($v['variation_amount'] ?? ($v['amount'] ?? 0));
            $fixed = (bool) ($v['fixedPrice'] ?? ($v['fixed'] ?? true));
            return [
                'code' => $code,
                'name' => $name,
                'amount' => $amount,
                'fixed' => $fixed,
                'convenience_fee' => $convenience,
                'total_debit' => round($amount + $convenience, 2),
            ];
        }, is_array($raw) ? $raw : []));

        $payload = [
            'bundles' => $bundles,
            'provider_response' => $json,
        ];
        \Illuminate\Support\Facades\Cache::put($cacheKey, $payload, $ttl);

        return response()->json([
            'network' => $network,
            'service_id' => $serviceId,
            'convenience_fee' => $convenience,
            'bundles' => $bundles,
            'provider_response' => $json,
            'stale' => false,
        ]);
    }

    public function tvBundles(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|in:dstv,gotv,startimes',
        ]);

        $service = strtolower($validated['service']);

        $baseUrl = rtrim(config('services.vtu.base_url', 'https://vtpass.com/api'), '/');
        $apiKey = config('services.vtu.api_key');
        $publicKey = config('services.vtu.public_key');
        $secretKey = config('services.vtu.secret_key');
        if (!$apiKey || (!$publicKey && !$secretKey)) {
            // Fallback to ClubKonnect packages when VTpass is not configured
            $ck = config('services.vtu.clubkonnect', []);
            $ckEnabled = (bool)($ck['enabled'] ?? false);
            $ckUser = $ck['user_id'] ?? null;
            $ckKey = $ck['api_key'] ?? null; // not used by plans endpoint but confirm creds exist
            $ckBase = rtrim((string)($ck['base_url'] ?? 'https://www.nellobytesystems.com'), '/');
            if ($ckEnabled && $ckUser && $ckKey) {
                try {
                    $r = \Illuminate\Support\Facades\Http::timeout(10)
                        ->acceptJson()
                        ->get($ckBase . '/APICableTVPackagesV2.asp', [ 'UserID' => $ckUser ]);
                    $j = $r->json();
                    if ($r->ok() && is_array($j)) {
                        $map = [ 'dstv' => ['DStv','dstv'], 'gotv' => ['GOtv','gotv'], 'startimes' => ['StarTimes','startimes','Startimes','STARTIMES'] ];
                        $keys = $map[$service] ?? [$service];
                        $raw = null;
                        foreach ($keys as $k) {
                            if (isset($j[$k]) && is_array($j[$k])) { $raw = $j[$k]; break; }
                            if (isset($j['data'][$k]) && is_array($j['data'][$k])) { $raw = $j['data'][$k]; break; }
                            if (isset($j['packages'][$k]) && is_array($j['packages'][$k])) { $raw = $j['packages'][$k]; break; }
                        }
                        if ($raw === null && isset($j['data']) && is_array($j['data'])) { $raw = $j['data']; }
                        if ($raw === null && isset($j['packages']) && is_array($j['packages'])) { $raw = $j['packages']; }
                        if ($raw === null && array_is_list($j)) { $raw = $j; }

                        $convenience = (float) (config('services.vtu.convenience_fee', 0));
                        $bundles = [];
                        if (is_array($raw)) {
                            foreach ($raw as $p) {
                                $code = (string)($p['code'] ?? ($p['package_code'] ?? ($p['id'] ?? '')));
                                $name = (string)($p['name'] ?? ($p['description'] ?? ''));
                                $amount = (float)($p['amount'] ?? ($p['price'] ?? ($p['cost'] ?? 0)));
                                if ($code === '') { continue; }
                                $bundles[] = [
                                    'code' => $code,
                                    'name' => $name,
                                    'amount' => $amount,
                                    'fixed' => true,
                                    'convenience_fee' => $convenience,
                                    'total_debit' => round($amount + $convenience, 2),
                                ];
                            }
                        }
                        if (!empty($bundles)) {
                            return response()->json([
                                'service' => $service,
                                'convenience_fee' => $convenience,
                                'bundles' => $bundles,
                                'provider_response' => $j,
                                'note' => 'Fetched from ClubKonnect',
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ClubKonnect TV packages fetch failed', ['error' => $e->getMessage()]);
                }
            }
            return response()->json(['message' => 'Provider not configured'], 500);
        }

        $headers = [ 'api-key' => $apiKey ];
        if ($publicKey) { $headers['public-key'] = $publicKey; }
        if ($secretKey) { $headers['secret-key'] = $secretKey; }

        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->get($baseUrl . '/service-variations', [ 'serviceID' => $service ]);
        } catch (\Throwable $e) {
            Log::error('VTU TV variations HTTP error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Network error'], 502);
        }

        $json = $resp->json();
        if (!$resp->ok()) {
            Log::error('VTU TV variations bad response', ['status' => $resp->status(), 'body' => $json]);
            return response()->json(['message' => 'Failed to fetch TV bundles', 'details' => $json], 502);
        }

        $raw = $json['content']['varations'] ?? $json['content']['variations'] ?? $json['data']['variations'] ?? [];
        $convenience = (float) (config('services.vtu.convenience_fee', 0));
        $bundles = array_values(array_map(function ($v) use ($convenience) {
            $code = $v['variation_code'] ?? ($v['code'] ?? null);
            $name = $v['name'] ?? '';
            $amount = (float) ($v['variation_amount'] ?? ($v['amount'] ?? 0));
            $fixed = (bool) ($v['fixedPrice'] ?? ($v['fixed'] ?? true));
            return [
                'code' => $code,
                'name' => $name,
                'amount' => $amount,
                'fixed' => $fixed,
                'convenience_fee' => $convenience,
                'total_debit' => round($amount + $convenience, 2),
            ];
        }, is_array($raw) ? $raw : []));

        return response()->json([
            'service' => $service,
            'convenience_fee' => $convenience,
            'bundles' => $bundles,
            'provider_response' => $json,
        ]);
    }

    public function purchaseElectricity(Request $request)
    {
        $validated = $request->validate([
            'disco' => 'required|string',
            'meter_number' => 'required|string|min:6',
            'meter_type' => 'required|in:prepaid,postpaid',
            'amount' => 'required|numeric|min:100',
            'phone_number' => 'nullable|string|min:10|max:15',
            'reference' => 'nullable|string|max:100',
            'pin' => ['required','regex:/^\d{4}$/'],
        ]);

        $user = $request->user();
        // Enforce Transaction PIN
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }
        $amount = (float)$validated['amount'];
        $convenience = (float) (config('services.vtu.convenience_fee', 0));
        $totalDebit = round($amount + $convenience, 2);
        if ((float)$user->balance < $totalDebit) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = $validated['reference'] ?? $this->generateReference('ELEC', $user->id);
        $reference = $this->ensureVtpassReference($reference);
        if (UtilityTransaction::where('reference', $reference)->exists()) {
            return response()->json(['message' => 'Duplicate reference'], 409);
        }

        $serviceId = strtolower(trim($validated['disco']));
        $meter = trim($validated['meter_number']);
        $meterType = strtolower($validated['meter_type']);
        $phone = isset($validated['phone_number']) ? $this->normalizeMsisdn($validated['phone_number']) : null;

        $discount = (float) (config('services.vtu.default_discount', 0.03));
        $costPrice = round($amount * (1 - max(0, min(1, $discount))), 2);

        $tx = UtilityTransaction::create([
            'user_id' => $user->id,
            'type' => 'electricity',
            'network' => $serviceId,
            'phone_number' => $meter,
            'amount' => $totalDebit,
            'cost_price' => $costPrice,
            'profit' => 0,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $payload = [
            'request_id' => $reference,
            'serviceID' => $serviceId,
            'billersCode' => $meter,
            'variation_code' => $meterType,
            'amount' => $amount,
        ];
        if (!empty($phone)) { $payload['phone'] = $phone; }

        $callbackUrl = trim((string) config('services.vtu.webhook_url'));
        if (!empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = $this->callVtuSmart('electricity', $payload);
        $providerUsed = $response['provider_used'] ?? 'vtpass';
        if (!$response['ok']) {
            $tx->update([
                'status' => 'failed',
                'provider_response' => $response['body'],
            ]);
            $status = isset($response['status']) ? (int)$response['status'] : null;
            $httpStatus = 502;
            if ($status !== null && $status >= 400 && $status < 500) {
                $httpStatus = 400;
            }
            return response()->json([
                'message' => 'Failed to vend electricity',
                'errors' => $response['error'] ?? 'Provider error',
                'provider' => $response['body'] ?? null,
                'reference' => $reference,
            ], $httpStatus);
        }

        $body = $response['body'];
        $success = $this->isVtpassSuccess($body);
        if (!$success) {
            if ($this->isVtpassPending($body)) {
                if (($providerUsed ?? 'vtpass') === 'vtpass') {
                    $requery = $this->requeryVtpass($reference);
                    if ($requery['ok']) {
                        $rb = $requery['body'];
                        if ($this->isVtpassSuccess($rb)) {
                            $body = $rb;
                        } elseif ($this->isVtpassPending($rb)) {
                            $tx->update([
                                'status' => 'pending',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Electricity vend is processing with provider. Check history for final status shortly.',
                                'status' => 'pending',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 200);
                        } else {
                            $tx->update([
                                'status' => 'failed',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Electricity vend failed',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 400);
                        }
                    } else {
                        $tx->update([
                            'status' => 'pending',
                            'provider_response' => $body,
                        ]);
                        return response()->json([
                            'message' => 'Electricity vend is processing. Unable to confirm now; please check history soon.',
                            'status' => 'pending',
                            'provider' => $requery['body'] ?? $body,
                            'reference' => $reference,
                        ], 200);
                    }
                } else {
                    $tx->update([
                        'status' => 'pending',
                        'provider_response' => $body,
                    ]);
                    return response()->json([
                        'message' => 'Electricity vend is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
            } else {
                $isSandbox = (bool) config('services.vtu.sandbox');
                $tx->update([
                    'status' => $isSandbox ? 'pending' : 'failed',
                    'provider_response' => $body,
                ]);
                if ($isSandbox) {
                    return response()->json([
                        'message' => 'Electricity vend is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
                return response()->json([
                    'message' => 'Electricity vend failed',
                    'provider' => $body,
                    'reference' => $reference,
                ], 400);
            }
        }

        $insufficient3 = false;
        DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $body, $convenience, $serviceId, $meter, $meterType, &$insufficient3) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
            if ((float)$lockedUser->balance < (float)$totalDebit) {
                $tx->update([
                    'status' => 'pending',
                    'provider_response' => $body,
                ]);
                $insufficient3 = true;
                return;
            }

            $lockedUser->decrement('balance', $totalDebit);
            $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
            $tx->update([
                'status' => 'success',
                'profit' => $profit,
                'provider_response' => $body,
            ]);
            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $totalDebit,
                'reference' => $reference,
                'source' => 'vtu_electricity',
                'meta' => [
                    'disco' => $serviceId,
                    'meter_number' => $meter,
                    'meter_type' => $meterType,
                    'utility_tx_id' => $tx->id,
                    'convenience_fee' => $convenience,
                ],
            ]);
        });

        if ($insufficient3) {
            $user->refresh();
            return response()->json([
                'message' => 'Electricity vend is processing. Wallet will be debited when funds are available.',
                'status' => 'pending',
                'reference' => $reference,
                'balance' => (float)$user->balance,
                'transaction' => $tx->fresh(),
            ], 202);
        }

        $user->refresh();
        try {
            $sms = app(\App\Services\SmsService::class);
            $msg = 'Electricity vend: ₦'.number_format($totalDebit, 2).' to meter '.($meter).' ('.strtoupper($serviceId).'). Ref: '.$reference.'. Bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {}

        return response()->json([
            'message' => 'Electricity token vended!',
            'status' => 'success',
            'reference' => $reference,
            'balance' => (float)$user->balance,
            'transaction' => $tx->fresh(),
        ]);
    }

    public function purchaseCable(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|in:dstv,gotv,startimes',
            'smartcard_number' => 'required|string|min:6',
            'bundle_code' => 'required|string',
            'amount' => 'required|numeric|min:100',
            'phone_number' => 'nullable|string|min:10|max:15',
            'reference' => 'nullable|string|max:100',
            'pin' => ['required','regex:/^\d{4}$/'],
        ]);

        $user = $request->user();
        // Enforce Transaction PIN
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }
        $amount = (float)$validated['amount'];
        $convenience = (float) (config('services.vtu.convenience_fee', 0));
        $totalDebit = round($amount + $convenience, 2);
        if ((float)$user->balance < $totalDebit) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = $validated['reference'] ?? $this->generateReference('CABLE', $user->id);
        $reference = $this->ensureVtpassReference($reference);
        if (UtilityTransaction::where('reference', $reference)->exists()) {
            return response()->json(['message' => 'Duplicate reference'], 409);
        }

        $service = strtolower(trim($validated['service']));
        $smartcard = trim($validated['smartcard_number']);
        $bundleCode = $validated['bundle_code'];
        $phone = isset($validated['phone_number']) ? $this->normalizeMsisdn($validated['phone_number']) : null;

        $discount = (float) (config('services.vtu.default_discount', 0.03));
        $costPrice = round($amount * (1 - max(0, min(1, $discount))), 2);

        $tx = UtilityTransaction::create([
            'user_id' => $user->id,
            'type' => 'cable',
            'network' => $service,
            'phone_number' => $smartcard,
            'amount' => $totalDebit,
            'cost_price' => $costPrice,
            'profit' => 0,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $payload = [
            'request_id' => $reference,
            'serviceID' => $service,
            'billersCode' => $smartcard,
            'variation_code' => $bundleCode,
            'amount' => $amount,
        ];
        if (!empty($phone)) { $payload['phone'] = $phone; }

        $callbackUrl = trim((string) config('services.vtu.webhook_url'));
        if (!empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = $this->callVtuSmart('cable', $payload);
        $providerUsed = $response['provider_used'] ?? 'vtpass';
        if (!$response['ok']) {
            $tx->update([
                'status' => 'failed',
                'provider_response' => $response['body'],
            ]);
            $status = isset($response['status']) ? (int)$response['status'] : null;
            $httpStatus = 502;
            if ($status !== null && $status >= 400 && $status < 500) {
                $httpStatus = 400;
            }
            return response()->json([
                'message' => 'Failed to process cable subscription',
                'errors' => $response['error'] ?? 'Provider error',
                'provider' => $response['body'] ?? null,
                'reference' => $reference,
            ], $httpStatus);
        }

        $body = $response['body'];
        $success = $this->isVtpassSuccess($body);
        if (!$success) {
            if ($this->isVtpassPending($body)) {
                if (($providerUsed ?? 'vtpass') === 'vtpass') {
                    $requery = $this->requeryVtpass($reference);
                    if ($requery['ok']) {
                        $rb = $requery['body'];
                        if ($this->isVtpassSuccess($rb)) {
                            $body = $rb;
                        } elseif ($this->isVtpassPending($rb)) {
                            $tx->update([
                                'status' => 'pending',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Cable subscription is processing with provider. Check history for final status shortly.',
                                'status' => 'pending',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 200);
                        } else {
                            $tx->update([
                                'status' => 'failed',
                                'provider_response' => $rb,
                            ]);
                            return response()->json([
                                'message' => 'Cable subscription failed',
                                'provider' => $rb,
                                'reference' => $reference,
                            ], 400);
                        }
                    } else {
                        $tx->update([
                            'status' => 'pending',
                            'provider_response' => $body,
                        ]);
                        return response()->json([
                            'message' => 'Cable subscription is processing. Unable to confirm now; please check history soon.',
                            'status' => 'pending',
                            'provider' => $requery['body'] ?? $body,
                            'reference' => $reference,
                        ], 200);
                    }
                } else {
                    // For non-VTpass providers, do not requery here; allow webhook or later reconciliation
                    $tx->update([
                        'status' => 'pending',
                        'provider_response' => $body,
                    ]);
                    return response()->json([
                        'message' => 'Cable subscription is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
            } else {
                $isSandbox = (bool) config('services.vtu.sandbox');
                $tx->update([
                    'status' => $isSandbox ? 'pending' : 'failed',
                    'provider_response' => $body,
                ]);
                if ($isSandbox) {
                    return response()->json([
                        'message' => 'Cable subscription is processing with provider. Check history for final status shortly.',
                        'status' => 'pending',
                        'provider' => $body,
                        'reference' => $reference,
                    ], 200);
                }
                return response()->json([
                    'message' => 'Cable subscription failed',
                    'provider' => $body,
                    'reference' => $reference,
                ], 400);
            }
        }

        DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $body, $convenience, $service, $smartcard, $bundleCode) {
            $user->decrement('balance', $totalDebit);
            $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
            $tx->update([
                'status' => 'success',
                'profit' => $profit,
                'provider_response' => $body,
            ]);
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $totalDebit,
                'reference' => $reference,
                'source' => 'vtu_cable',
                'meta' => [
                    'service' => $service,
                    'smartcard_number' => $smartcard,
                    'bundle_code' => $bundleCode,
                    'utility_tx_id' => $tx->id,
                    'convenience_fee' => $convenience,
                ],
            ]);
        });

        $user->refresh();
        try {
            $sms = app(\App\Services\SmsService::class);
            $msg = 'Cable subscription: ₦'.number_format($totalDebit, 2).' for '.strtoupper($service).' ('.($bundleCode).') on '.($smartcard).'. Ref: '.$reference.'. Bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {}

        return response()->json([
            'message' => 'Cable subscribed!',
            'status' => 'success',
            'reference' => $reference,
            'balance' => (float)$user->balance,
            'transaction' => $tx->fresh(),
        ]);
    }

    private function emptyPage(int $page = 1, int $perPage = 15): array
    {
        $basePath = url('/api/vtu/transactions');
        return [
            'current_page' => $page,
            'data' => [],
            'first_page_url' => $basePath . '?page=1',
            'from' => null,
            'last_page' => 1,
            'last_page_url' => $basePath . '?page=1',
            'links' => [
                ['url' => null, 'label' => '&laquo; Previous', 'active' => false],
                ['url' => $basePath . '?page=1', 'label' => '1', 'active' => true],
                ['url' => null, 'label' => 'Next &raquo;', 'active' => false],
            ],
            'next_page_url' => null,
            'path' => $basePath,
            'per_page' => $perPage,
            'prev_page_url' => null,
            'to' => null,
            'total' => 0,
        ];
    }

    private function normalizeNetwork(string $network): string
    {
        $n = strtolower($network);
        if ($n === 'etisalat') { // alias
            $n = '9mobile';
        }
        return $n;
    }

    private function airtimeServiceId(string $network): string
    {
        // VTpass historically used 'etisalat' as the serviceID for 9mobile
        if ($network === '9mobile') {
            return 'etisalat';
        }
        return $network;
    }

    private function dataServiceId(string $network): string
    {
        // For VTpass, data service IDs are typically '{network}-data'
        if ($network === '9mobile') {
            return 'etisalat-data';
        }
        return $network . '-data';
    }

    private function generateReference(string $prefix, int $userId): string
    {
        // VTpass requires request_id to start with current UTC datetime (YYYYMMDDHHmm) + unique string
        // Avoid any prefixes like "VTU-AIRTIME-" to prevent provider rejection.
        return now('Africa/Lagos')->format('YmdHi') . Str::random(8);

    }

    // Ensure a client-supplied reference meets VTpass requirements
    private function ensureVtpassReference(string $reference): string
    {
        $ref = trim((string) $reference);
        if ($ref === '') {
            return $this->generateReference('AUTO', 0);
        }
        // Collapse whitespace
        $ref = preg_replace('/\s+/', '', $ref);
        $prefix = gmdate('YmdHi');
        // If it doesn't start with the required UTC timestamp, generate a compliant one
        if (!str_starts_with($ref, $prefix)) {
            return $prefix . Str::lower(Str::random(6));
        }
        // Enforce maximum length
        if (strlen($ref) > 100) {
            $ref = substr($ref, 0, 100);
        }
        return $ref;
    }

    private function callVtuSmart(string $type, array $payload): array
    {
        // Smart router: ClubKonnect -> Shago -> VTPass
        $order = array_filter(array_map('trim', explode(',', (string) config('services.vtu.routing_order', 'clubkonnect,shago,vtpass'))));
        $lastError = null;

        foreach ($order as $provider) {
            $provider = strtolower($provider);
            if ($provider === 'clubkonnect') {
                $resp = $this->callClubKonnect($type, $payload);
            } elseif ($provider === 'shago') {
                $resp = $this->callShago($type, $payload);
            } elseif ($provider === 'vtpass') {
                $resp = $this->callVtpass($payload);
            } else {
                continue;
            }

            // If provider not configured, skip to next
            if (($resp['status'] ?? null) === 0 && ($resp['error'] ?? '') === 'Provider not configured') {
                $lastError = $resp;
                continue;
            }

            if (!$resp['ok']) {
                $lastError = $resp; // network or http error, try next
                continue;
            }

            $body = $resp['body'] ?? null;
            // If already a success, return immediately
            if ($this->isVtpassSuccess($body)) {
                return array_merge($resp, ['provider_used' => $provider]);
            }
            // If pending, do not failover; return pending so requery/webhook can finalize
            if ($this->isVtpassPending($body)) {
                return array_merge($resp, ['provider_used' => $provider]);
            }

            // Otherwise, treat as provider-declared failure: try next provider
            $lastError = $resp;
        }

        return $lastError ?: [ 'ok' => false, 'error' => 'No VTU provider available', 'body' => null, 'status' => 0 ];
    }

    private function callClubKonnect(string $type, array $payload): array
    {
        // Nellobytes/ClubKonnect direct API integration (airtime, data, cable)
        $cfg = config('services.vtu.clubkonnect', []);
        $enabled = (bool)($cfg['enabled'] ?? false);
        $userId = $cfg['user_id'] ?? null;
        $apiKey = $cfg['api_key'] ?? null;
        $baseUrl = rtrim((string)($cfg['base_url'] ?? 'https://www.nellobytesystems.com'), '/');
        if (!$enabled || !$userId || !$apiKey) {
            return [ 'ok' => false, 'error' => 'Provider not configured', 'body' => null, 'status' => 0 ];
        }

        $cb = trim((string) config('services.vtu.webhook_url'));
        $requestId = $payload['request_id'] ?? ($payload['RequestID'] ?? ($payload['requestId'] ?? null));
        if ($cb !== '' && $requestId) {
            $sep = (str_contains($cb, '?') ? '&' : '?');
            $cb = $cb . $sep . 'ref=' . $requestId;
        }

        $endpoint = null;
        $params = [ 'UserID' => $userId, 'APIKey' => $apiKey ];

        if ($type === 'airtime') {
            // Map network to ClubKonnect MobileNetwork codes (airtime mapping)
            $network = strtolower((string)($payload['serviceID'] ?? $payload['network'] ?? ''));
            if ($network === 'etisalat') { $network = '9mobile'; }
            $mapAirtime = [ 'mtn' => '01', 'glo' => '02', 'airtel' => '03', '9mobile' => '04' ];
            $mobileNetwork = $mapAirtime[$network] ?? null;

            $amount = $payload['amount'] ?? null;
            $mobileNumber = $payload['phone'] ?? $payload['billersCode'] ?? null;
            if (!$mobileNetwork || !$amount || !$mobileNumber || !$requestId) {
                return [ 'ok' => false, 'error' => 'Missing required fields', 'body' => [ 'note' => 'network/amount/phone/request_id required' ], 'status' => 0 ];
            }

            $endpoint = '/APIAirtimeV1.asp';
            $params = array_merge($params, [
                'MobileNetwork' => $mobileNetwork,
                'Amount' => $amount,
                'MobileNumber' => $mobileNumber,
                'RequestID' => $requestId,
            ]);
            if ($cb !== '') { $params['CallBackURL'] = $cb; }
            $bonus = $payload['bonus_type'] ?? ($payload['BonusType'] ?? ($payload['bonusType'] ?? null));
            if (!empty($bonus)) { $params['BonusType'] = $bonus; }
        } elseif ($type === 'data') {
            // Data bundle purchase via APIDatabundleV1.asp
            // Network codes per spec: 01 MTN, 02 Glo, 03 9mobile, 04 Airtel
            $serviceId = strtolower((string)($payload['serviceID'] ?? ''));
            $network = $payload['network'] ?? $serviceId;
            if (str_contains($serviceId, '-data')) {
                $network = explode('-data', $serviceId)[0];
            }
            $network = strtolower((string) $network);
            if ($network === 'etisalat') { $network = '9mobile'; }
            $mapData = [ 'mtn' => '01', 'glo' => '02', '9mobile' => '03', 'airtel' => '04' ];
            $mobileNetwork = $mapData[$network] ?? null;

            $dataPlan = $payload['variation_code'] ?? ($payload['DataPlan'] ?? null);
            $mobileNumber = $payload['phone'] ?? $payload['billersCode'] ?? null;
            if (!$mobileNetwork || !$dataPlan || !$mobileNumber || !$requestId) {
                return [ 'ok' => false, 'error' => 'Missing required fields', 'body' => [ 'note' => 'network/dataplan/phone/request_id required' ], 'status' => 0 ];
            }

            $endpoint = '/APIDatabundleV1.asp';
            $params = array_merge($params, [
                'MobileNetwork' => $mobileNetwork,
                'DataPlan' => $dataPlan,
                'MobileNumber' => $mobileNumber,
                'RequestID' => $requestId,
            ]);
            if ($cb !== '') { $params['CallBackURL'] = $cb; }
        } elseif ($type === 'cable') {
            // Cable subscription via APICableTVV1.asp
            $service = strtolower((string)($payload['serviceID'] ?? ''));
            $package = $payload['variation_code'] ?? ($payload['Package'] ?? null);
            $smartcard = $payload['billersCode'] ?? ($payload['SmartCardNo'] ?? null);
            $phone = $payload['phone'] ?? ($payload['PhoneNo'] ?? null);
            if (!$service || !$package || !$smartcard || !$requestId) {
                return [ 'ok' => false, 'error' => 'Missing required fields', 'body' => [ 'note' => 'service/package/smartcard/request_id required' ], 'status' => 0 ];
            }

            $endpoint = '/APICableTVV1.asp';
            $params = array_merge($params, [
                'CableTV' => $service,
                'Package' => $package,
                'SmartCardNo' => $smartcard,
                'RequestID' => $requestId,
            ]);
            if (!empty($phone)) { $params['PhoneNo'] = $phone; }
            if ($cb !== '') { $params['CallBackURL'] = $cb; }
        } else {
            return [ 'ok' => false, 'error' => 'Unsupported channel', 'body' => null, 'status' => 0 ];
        }

        $status = 0; $ok = false; $bodyOut = null; $error = null;
        try {
            $resp = Http::timeout(12)
                ->acceptJson()
                ->get($baseUrl . $endpoint, $params);
            $status = $resp->status();
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('ClubKonnect bad response', ['status' => $status, 'body' => $json, 'endpoint' => $endpoint]);
                return [ 'ok' => false, 'error' => 'Bad response', 'body' => (is_array($json)?$json:['raw' => $resp->body()]), 'status' => $status ];
            }
            // Pass through JSON; success/pending are detected by isVtpassSuccess/isVtpassPending via statuscode/orderstatus
            $bodyOut = is_array($json) ? $json : [ 'raw' => $resp->body() ];
            $ok = true;
        } catch (\Throwable $e) {
            Log::error('ClubKonnect HTTP error', ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            $error = 'Network error';
        }
        return [ 'ok' => $ok, 'error' => $error, 'body' => $bodyOut, 'status' => $status ];
    }

    private function callShago(string $type, array $payload): array
    {
        $cfg = config('services.vtu.shago', []);
        if (empty($cfg['enabled']) || empty($cfg['base_url']) || empty($cfg['api_key'])) {
            return [ 'ok' => false, 'error' => 'Provider not configured', 'body' => null, 'status' => 0 ];
        }
        $baseUrl = rtrim((string)$cfg['base_url'], '/');
        $headers = [ 'Authorization' => 'Bearer '.$cfg['api_key'] ];
        if (!empty($cfg['secret'])) {
            $headers['X-Secret'] = $cfg['secret'];
        }
        $bodyOut = null; $status = 0; $ok = false; $error = null;
        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(12)
                ->post($baseUrl . '/pay', array_merge($payload, [ 'channel' => $type ]));
            $status = $resp->status();
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('Shago bad response', ['status' => $status, 'body' => $json]);
                return [ 'ok' => false, 'error' => 'Bad response', 'body' => $json, 'status' => $status ];
            }
            $st = strtolower((string)($json['status'] ?? ''));
            $code = (string)($json['code'] ?? '');
            $success = ($st === 'success' || $st === 'successful' || $code === '00' || $code === '000' || ($json['success'] ?? false) === true);
            $bodyOut = [
                'code' => $success ? '000' : ($code ?: 'XXX'),
                'status' => $success ? 'success' : ($st ?: 'failed'),
                'message' => (string)($json['message'] ?? ''),
                'data' => $json,
                'provider' => 'shago',
            ];
            $ok = true;
        } catch (\Throwable $e) {
            Log::error('Shago HTTP error', ['error' => $e->getMessage()]);
            $error = 'Network error';
        }
        return [ 'ok' => $ok, 'error' => $error, 'body' => $bodyOut, 'status' => $status ];
    }

    private function callVtpass(array $payload): array
    {
        $baseUrl = rtrim(config('services.vtu.base_url', 'https://vtpass.com/api'), '/');
        $apiKey = config('services.vtu.api_key');
        $publicKey = config('services.vtu.public_key');
        $secretKey = config('services.vtu.secret_key');

        if (!$apiKey || (!$publicKey && !$secretKey)) {
            Log::warning('VTU provider keys not configured');
            return [
                'ok' => false,
                'error' => 'Provider not configured',
                'body' => null,
            ];
        }

        $headers = [
            'api-key' => $apiKey,
        ];
        if ($publicKey) {
            $headers['public-key'] = $publicKey;
        }
        if ($secretKey) {
            $headers['secret-key'] = $secretKey;
        }

        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->post($baseUrl . '/pay', $payload);
        } catch (\Throwable $e) {
            Log::error('VTU provider HTTP error', ['exception' => $e->getMessage()]);
            return [
                'ok' => false,
                'error' => 'Network error',
                'body' => null,
                'status' => 0,
            ];
        }

        $json = $resp->json();
        if (!$resp->ok()) {
            Log::error('VTU provider responded with error', ['status' => $resp->status(), 'body' => $json]);
            return [
                'ok' => false,
                'error' => 'Bad response',
                'body' => $json,
                'status' => $resp->status(),
            ];
        }

        return [
            'ok' => true,
            'body' => $json,
            'status' => $resp->status(),
        ];
    }

    private function isVtpassSuccess($body): bool
    {
        if (is_array($body)) {
            // VTpass standard success code
            $code = (string)($body['code'] ?? ($body['data']['code'] ?? ''));
            if ($code === '000') return true;

            // Nellobytes/ClubKonnect success: statuscode=200 or orderstatus=ORDER_COMPLETED
            $ckCode = (string)($body['statuscode'] ?? ($body['status_code'] ?? ''));
            if ($ckCode === '200' || $ckCode === 'OK' || $ckCode === '201') { // be liberal
                return true;
            }
            $orderStatusUp = strtoupper((string)($body['orderstatus'] ?? ($body['order_status'] ?? '')));
            if (in_array($orderStatusUp, ['ORDER_COMPLETED', 'COMPLETED', 'SUCCESS'])) {
                return true;
            }

            // 2. Check for "success" or "successful" or "delivered" strings
            $status = strtolower((string)($body['status'] ?? ''));
            $respDesc = strtolower((string)($body['response_description'] ?? ''));
            $message = strtolower((string)($body['message'] ?? ''));

            if (in_array($status, ['success', 'successful', 'delivered', 'completed'])) {
                return true;
            }

            if (
                ($respDesc && (str_contains($respDesc, 'success') || str_contains($respDesc, 'delivered') || str_contains($respDesc, 'completed')))
                || ($message && (str_contains($message, 'success') || str_contains($message, 'delivered') || str_contains($message, 'completed')))
            ) {
                return true;
            }

            // 3. Check nested transaction content (Common in webhooks)
            $txStatus = strtolower((string)($body['content']['transactions']['status'] ?? ($body['data']['transactions']['status'] ?? ($body['transactions']['status'] ?? ''))));
            if (in_array($txStatus, ['completed', 'successful', 'delivered'])) {
                return true;
            }
        }
        return false;
    }

    private function isVtpassPending($body): bool
    {
        if (!is_array($body)) { return false; }
        $status = strtolower((string)($body['status'] ?? ''));
        if (in_array($status, ['pending', 'processing', 'initiated', 'queued', 'order_received'])) { return true; }
        $txStatus = strtolower((string)($body['data']['transactions']['status'] ?? ($body['content']['transactions']['status'] ?? ($body['transactions']['status'] ?? ''))));
        if (in_array($txStatus, ['pending', 'processing', 'initiated', 'queued'])) { return true; }
        // Nellobytes/ClubKonnect pending fields
        $ckCode = (string)($body['statuscode'] ?? ($body['status_code'] ?? ''));
        if ($ckCode === '100') { return true; }
        $orderStatusUp = strtoupper((string)($body['orderstatus'] ?? ($body['order_status'] ?? '')));
        if (in_array($orderStatusUp, ['ORDER_RECEIVED', 'RECEIVED'])) { return true; }
        $desc = strtolower((string)($body['response_description'] ?? ($body['message'] ?? '')));
        if ($desc && (str_contains($desc, 'pending') || str_contains($desc, 'processing') || str_contains($desc, 'initiated') || str_contains($desc, 'queue'))) { return true; }
        // Some VTpass variants use non-000 codes while processing
        $code = (string)($body['code'] ?? ($body['data']['code'] ?? ''));
        if (in_array($code, ['016', '099'])) { return true; }
        return false;
    }

    private function requeryVtpass(string $reference): array
    {
        $baseUrl = rtrim(config('services.vtu.base_url', 'https://vtpass.com/api'), '/');
        $apiKey = config('services.vtu.api_key');
        $publicKey = config('services.vtu.public_key');
        $secretKey = config('services.vtu.secret_key');

        if (!$apiKey || (!$publicKey && !$secretKey)) {
            Log::warning('VTU provider keys not configured for requery');
            return [
                'ok' => false,
                'error' => 'Provider not configured',
                'body' => null,
            ];
        }

        $headers = [ 'api-key' => $apiKey ];
        if ($publicKey) { $headers['public-key'] = $publicKey; }
        if ($secretKey) { $headers['secret-key'] = $secretKey; }

        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->get($baseUrl . '/requery', [ 'request_id' => $reference ]);
        } catch (\Throwable $e) {
            Log::error('VTU requery HTTP error', ['exception' => $e->getMessage()]);
            return [ 'ok' => false, 'error' => 'Network error', 'body' => null, 'status' => 0 ];
        }

        $json = $resp->json();
        if (!$resp->ok()) {
            Log::error('VTU requery bad response', ['status' => $resp->status(), 'body' => $json]);
            return [ 'ok' => false, 'error' => 'Bad response', 'body' => $json, 'status' => $resp->status() ];
        }

        return [ 'ok' => true, 'body' => $json, 'status' => $resp->status() ];
    }

    // Normalize Nigerian MSISDNs to 11-digit local format (0XXXXXXXXXX)
    private function normalizeMsisdn(string $msisdn): string
    {
        $digits = preg_replace('/[^0-9]/', '', $msisdn);
        if (!$digits) { return $msisdn; }

        // If starts with 234 and length >= 13 (e.g., 23480XXXXXXXX), convert to 0XXXXXXXXXX
        if (str_starts_with($digits, '234')) {
            $rest = substr($digits, 3);
            // If rest already starts with '0', keep rest as-is; otherwise prefix '0'
            if ($rest && $rest[0] !== '0') {
                $rest = '0' . $rest;
            }
            // Trim to 11 digits if longer
            return substr($rest, 0, 11);
        }

        // If 10 digits (e.g., 8031234567), prefix 0
        if (strlen($digits) === 10) {
            return '0' . $digits;
        }

        // If 11 digits starting with 0, return as-is
        if (strlen($digits) === 11 && $digits[0] === '0') {
            return $digits;
        }

        // Fallback to original input if we can't confidently normalize
        return $msisdn;
    }
}
