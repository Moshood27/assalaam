<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UtilityTransaction;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        Log::info('VTU Webhook Received', ['payload' => $request->all()]);

        // Extract request_id/reference from common fields
        $payload = $request->all();
        // Try our own cb_ref first (we append ?ref=reference in callback URLs)
        $cbRef = $request->query('ref') ?? ($payload['ref'] ?? null);
        $reference = $payload['request_id']
            ?? ($payload['requestId']
            ?? ($payload['requestid']
            ?? ($payload['reference']
            ?? ($payload['RequestID']
            ?? ($payload['data']['requestId']
            ?? ($payload['content']['transactions']['requestId'] ?? $cbRef))))));

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

    public function checkStatus(Request $request, string $orderId)
    {
        // Authenticated user can check only their own transaction by reference (RequestID)
        if (!Schema::hasTable('utility_transactions')) {
            return response()->json(['message' => 'Not available'], 404);
        }
        $user = $request->user();
        $tx = UtilityTransaction::where('user_id', $user->id)
            ->where('reference', $orderId)
            ->first();
        if (!$tx) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Determine provider and try appropriate requery
        $result = null;
        $source = null;
        $providerResp = is_array($tx->provider_response) ? $tx->provider_response : [];

        $isClubKonnect = isset($providerResp['orderid']) || isset($providerResp['statuscode']) || (isset($providerResp['status']) && str_contains((string)($providerResp['status']??''), 'ORDER'));
        $isVtpass = isset($providerResp['code']) || (isset($providerResp['content']) && isset($providerResp['content']['transactions']));

        if ($isClubKonnect) {
            // Try by OrderID if available (most reliable for ClubKonnect)
            $orderIdFromProvider = $providerResp['orderid'] ?? ($providerResp['order_id'] ?? null);
            if ($orderIdFromProvider) {
                $ckOrder = $this->requeryClubKonnectByOrderId((string)$orderIdFromProvider);
                if (($ckOrder['ok'] ?? false) && is_array($ckOrder['body'] ?? null) && !empty($ckOrder['body']['statuscode'])) {
                    $result = $ckOrder['body'];
                    $source = 'clubkonnect';
                }
            }

            // Fallback to RequestID (using the reference column)
            if (!$result) {
                $ck = $this->requeryClubKonnectByRequestId($orderId);
                if (($ck['ok'] ?? false) && is_array($ck['body'] ?? null) && !empty($ck['body']['statuscode'])) {
                    $result = $ck['body'];
                    $source = 'clubkonnect';
                }
            }
        } elseif ($isVtpass) {
            $vt = $this->requeryVtpass($orderId);
            if (($vt['ok'] ?? false) && is_array($vt['body'] ?? null)) {
                $result = $vt['body'];
                $source = 'vtpass';
            }
        } else {
            // Fallback for unknown provider: try both (CK first)
            $ck = $this->requeryClubKonnectByRequestId($orderId);
            if (($ck['ok'] ?? false) && is_array($ck['body'] ?? null) && !empty($ck['body']['statuscode'])) {
                $result = $ck['body'];
                $source = 'clubkonnect';
            } else {
                $vt = $this->requeryVtpass($orderId);
                if (($vt['ok'] ?? false) && is_array($vt['body'] ?? null)) {
                    $result = $vt['body'];
                    $source = 'vtpass';
                }
            }
        }

        if (!$result) {
            return response()->json([
                'message' => 'Still Processing',
                'status' => 'pending',
                'reference' => $orderId,
            ], 200);
        }

        $status = 'failed';
        if ($this->isVtpassSuccess($result)) {
            $status = 'success';
        } elseif ($this->isVtpassPending($result)) {
            $status = 'pending';
        }

        DB::transaction(function () use ($tx, $result, $status) {
            $user = $tx->user()->lockForUpdate()->first();
            // Persist latest provider response
            $tx->provider_response = $result;

            if ($status === 'success') {
                if ($tx->status !== 'success') {
                    $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                    $tx->status = 'success';
                    $tx->profit = $profit;

                    $hasDebit = WalletTransaction::where('reference', $tx->reference)
                        ->where('type', 'debit')
                        ->exists();
                    if (!$hasDebit) {
                        $debitAmount = (float) $tx->amount;
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
                                'requery' => true,
                            ],
                        ]);
                    }
                }
            } elseif ($status === 'failed') {
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
                                'requery' => true,
                            ],
                        ]);
                    }
                }
            } else {
                $tx->status = 'pending';
            }

            $tx->save();
        });

        $fresh = $tx->fresh();
        if ($fresh->status === 'success') {
            $msg = 'Delivered';
            if ($fresh->type === 'electricity') {
                $body = $fresh->provider_response ?? [];
                $token = $body['mainToken'] ?? ($body['token'] ?? ($body['purchased_code'] ?? ($body['data']['token'] ?? null)));
                if ($token) {
                    $msg = "Electricity token vended! Token: $token";
                }
            }
            return response()->json([
                'message' => $msg,
                'status' => 'success',
                'reference' => $orderId,
                'transaction' => $fresh,
            ], 200);
        }
        if ($fresh->status === 'pending') {
            return response()->json([
                'message' => 'Still Processing',
                'status' => 'pending',
                'reference' => $orderId,
                'transaction' => $fresh,
            ], 200);
        }
        return response()->json([
            'message' => 'Failed',
            'status' => 'failed',
            'reference' => $orderId,
            'transaction' => $fresh,
        ], 200);
    }

    public function cancelTransaction(Request $request, string $orderId)
    {
        $user = $request->user();
        $tx = UtilityTransaction::where('user_id', $user->id)
            ->where('reference', $orderId)
            ->first();

        if (!$tx) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($tx->status !== 'pending') {
            return response()->json(['message' => 'Only pending transactions can be cancelled'], 422);
        }

        // We can only cancel ClubKonnect transactions if we have an OrderID
        $providerResp = $tx->provider_response;
        $orderIdFromProvider = is_array($providerResp) ? ($providerResp['orderid'] ?? ($providerResp['order_id'] ?? null)) : null;

        if (!$orderIdFromProvider) {
            // Requery by RequestID to find OrderID if possible
            $ckRequery = $this->requeryClubKonnectByRequestId($orderId);
            if ($ckRequery['ok'] && isset($ckRequery['body']['orderid'])) {
                $orderIdFromProvider = $ckRequery['body']['orderid'];
            }
        }

        if (!$orderIdFromProvider) {
            return response()->json(['message' => 'Cannot cancel this transaction (OrderID unknown)'], 422);
        }

        $resp = $this->cancelClubKonnectByOrderId($orderIdFromProvider);
        if (!$resp['ok']) {
            return response()->json([
                'message' => 'Failed to cancel transaction with provider',
                'provider' => $resp['body'] ?? null,
            ], 400);
        }

        $body = $resp['body'] ?? [];
        $status = strtoupper((string)($body['status'] ?? ($body['orderstatus'] ?? '')));

        if (in_array($status, ['ORDER_CANCELLED', 'CANCELLED'])) {
            DB::transaction(function () use ($tx, $body) {
                $user = $tx->user()->lockForUpdate()->first();
                $tx->update([
                    'status' => 'cancelled',
                    'provider_response' => $body,
                ]);

                // Refund if debited (similar to failure)
                $hasDebit = WalletTransaction::where('reference', $tx->reference)
                    ->where('type', 'debit')
                    ->exists();

                if ($hasDebit) {
                    $refundRef = $tx->reference . '-CANCEL-REFUND';
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
                                'reason' => 'User cancelled',
                            ],
                        ]);
                    }
                }
            });

            return response()->json([
                'message' => 'Transaction cancelled successfully',
                'status' => 'cancelled',
                'reference' => $orderId,
            ]);
        }

        return response()->json([
            'message' => 'Cancellation not accepted by provider: ' . ($body['orderstatus'] ?? $status),
            'provider' => $body,
        ], 400);
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
        $providerUsed = $response['provider_used'] ?? 'clubkonnect';

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
                if (($providerUsed ?? '') === 'vtpass') {
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
                                'message' => 'Processing! Your airtime is on the way. Please check your balance in 1 minute.',
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
                            'message' => 'Processing! Your airtime is on the way. Please check your balance in 1 minute.',
                            'status' => 'pending',
                            'provider' => $requery['body'] ?? $body,
                            'reference' => $reference,
                        ], 200);
                    }
                } else {
                    // For ClubKonnect, perform a single immediate requery by RequestID to reduce false pendings
                    if (($providerUsed ?? '') === 'clubkonnect') {
                        $ckRequery = $this->requeryClubKonnectByRequestId($reference);
                        if ($ckRequery['ok']) {
                            $ckb = $ckRequery['body'];
                            if ($this->isVtpassSuccess($ckb)) {
                                // Treat as success below; set $body to requery body so we persist it
                                $body = $ckb;
                            } elseif ($this->isVtpassPending($ckb)) {
                                // ClubKonnect accepted (100). Debit member immediately and mark pending to protect Coop funds.
                                DB::transaction(function () use ($user, $amount, $reference, $tx, $ckb) {
                                    $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                    if ((float)$lockedUser->balance >= (float)$amount) {
                                        $lockedUser->decrement('balance', $amount);

                                        $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                        $tx->update([
                                            'status' => 'pending',
                                            'profit' => $profit,
                                            'provider_response' => $ckb,
                                            'order_id' => $ckb['orderid'] ?? null,
                                            'provider' => 'clubkonnect',
                                        ]);

                                        WalletTransaction::create([
                                            'user_id' => $lockedUser->id,
                                            'type' => 'debit',
                                            'amount' => $amount,
                                            'reference' => $ckb['orderid'] ?? $tx->reference,
                                            'source' => 'vtu_airtime',
                                            'meta' => [
                                                'network' => $tx->network,
                                                'phone_number' => $tx->phone_number,
                                                'utility_tx_id' => $tx->id,
                                                'provider' => 'clubkonnect',
                                                'status' => 'pending',
                                            ],
                                        ]);
                                    } else {
                                        // Not enough funds at this exact moment; keep pending without debit
                                        $tx->update([
                                            'status' => 'pending',
                                            'provider_response' => $ckb,
                                        ]);
                                    }
                                });
                                return response()->json([
                                    'message' => 'Processing! Your airtime is on the way. Please check your balance in 1 minute.',
                                    'status' => 'pending',
                                    'provider' => $ckb,
                                    'reference' => $tx->fresh()->reference,
                                ], 200);
                            } else {
                                // Provider-declared failure after requery
                                $tx->update([
                                    'status' => 'failed',
                                    'provider_response' => $ckb,
                                ]);
                                return response()->json([
                                    'message' => 'Airtime purchase failed',
                                    'provider' => $ckb,
                                    'reference' => $reference,
                                ], 400);
                            }
                        } else {
                            // Requery failed (network or non-OK). Since ClubKonnect already accepted (100), debit immediately and keep tx pending.
                            DB::transaction(function () use ($user, $amount, $reference, $tx, $body) {
                                $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                if ((float)$lockedUser->balance >= (float)$amount) {
                                    $lockedUser->decrement('balance', $amount);

                                    $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                    $tx->update([
                                        'status' => 'pending',
                                        'profit' => $profit,
                                        'provider_response' => $body,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                    ]);

                                    WalletTransaction::create([
                                        'user_id' => $lockedUser->id,
                                        'type' => 'debit',
                                        'amount' => $amount,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                        'source' => 'vtu_airtime',
                                        'meta' => [
                                            'network' => $tx->network,
                                            'phone_number' => $tx->phone_number,
                                            'utility_tx_id' => $tx->id,
                                            'status' => 'pending',
                                        ],
                                    ]);
                                } else {
                                    // Not enough funds at this exact moment; keep pending without debit
                                    $tx->update([
                                        'status' => 'pending',
                                        'provider_response' => $body,
                                    ]);
                                }
                            });
                            return response()->json([
                                'message' => 'Order received and processing.',
                                'status' => 'pending',
                                'provider' => $ckRequery['body'] ?? $body,
                                'reference' => $tx->fresh()->reference,
                            ], 200);
                        }
                    } else {
                        // For other non-VTpass providers, do not requery here; allow webhook or later reconciliation
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
                'order_id' => $body['orderid'] ?? ($body['order_id'] ?? ($body['content']['transactions']['order_id'] ?? ($body['content']['transactions']['transactionId'] ?? null))),
                'provider' => $providerUsed ?? 'clubkonnect',
            ]);

            // Record wallet debit transaction
            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $body['orderid'] ?? ($body['requestId'] ?? $tx->reference),
                'source' => 'vtu_airtime',
                'meta' => [
                    'network' => $tx->network,
                    'phone_number' => $tx->phone_number,
                    'utility_tx_id' => $tx->id,
                    'provider' => $providerUsed ?? 'clubkonnect',
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
            $msg = 'Airtime purchased: ₦' . number_format($amount, 2) . ' for ' . ($tx->phone_number) . '. Ref: ' . $reference . '. Bal: ₦' . number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        return response()->json([
            'message' => 'Airtime sent!',
            'status' => 'success',
            'reference' => $tx->fresh()->reference,
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
            'vtu_provider' => 'nullable|string',
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
        $provider = $validated['vtu_provider'] ?? config('services.vtu.provider');

        if ($provider === 'clubkonnect') {
            $response = $this->callClubKonnect('data', $payload);
        } elseif ($provider === 'shago') {
            $response = $this->callShago('data', $payload);
        } elseif ($provider === 'vtpass') {
            $response = $this->callVtpass('data', $payload);
        } else {
            $response = $this->callVtuSmart('data', $payload);
        }
        $providerUsed = $response['provider_used'] ?? $provider;

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
                if (($providerUsed ?? '') === 'vtpass') {
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
                                'message' => 'Processing! Your data is on the way. Please check your balance in 1 minute.',
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
                    // For ClubKonnect, perform a single immediate requery by RequestID to reduce false pendings
                    if (($providerUsed ?? '') === 'clubkonnect') {
                        $ckRequery = $this->requeryClubKonnectByRequestId($reference);
                        if ($ckRequery['ok']) {
                            $ckb = $ckRequery['body'];
                            if ($this->isVtpassSuccess($ckb)) {
                                // Treat as success below; set $body to requery body so we persist it
                                $body = $ckb;
                            } elseif ($this->isVtpassPending($ckb)) {
                                // ClubKonnect accepted (100). Debit member immediately and mark pending to protect Coop funds.
                                DB::transaction(function () use ($user, $amount, $reference, $tx, $ckb, $convenience, $bundleCode) {
                                    $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                    $debit = round($amount + $convenience, 2);
                                    if ((float)$lockedUser->balance >= (float)$debit) {
                                        $lockedUser->decrement('balance', $debit);

                                        $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                        $tx->update([
                                            'status' => 'pending',
                                            'profit' => $profit,
                                            'provider_response' => $ckb,
                                            'order_id' => $ckb['orderid'] ?? null,
                                            'provider' => 'clubkonnect',
                                        ]);

                                        WalletTransaction::create([
                                            'user_id' => $lockedUser->id,
                                            'type' => 'debit',
                                            'amount' => $debit,
                                            'reference' => $ckb['orderid'] ?? $tx->reference,
                                            'source' => 'vtu_data',
                                            'meta' => [
                                                'network' => $tx->network,
                                                'phone_number' => $tx->phone_number,
                                                'bundle_code' => $bundleCode,
                                                'utility_tx_id' => $tx->id,
                                                'provider' => 'clubkonnect',
                                                'convenience_fee' => $convenience,
                                                'status' => 'pending',
                                            ],
                                        ]);
                                    } else {
                                        // Not enough funds at this exact moment; keep pending without debit
                                        $tx->update([
                                            'status' => 'pending',
                                            'provider_response' => $ckb,
                                        ]);
                                    }
                                });
                                return response()->json([
                                    'message' => 'Processing! Your data is on the way. Please check your balance in 1 minute.',
                                    'status' => 'pending',
                                    'provider' => $ckb,
                                    'reference' => $tx->fresh()->reference,
                                ], 200);
                            } else {
                                // Provider-declared failure after requery
                                $tx->update([
                                    'status' => 'failed',
                                    'provider_response' => $ckb,
                                ]);
                                return response()->json([
                                    'message' => 'Data purchase failed',
                                    'provider' => $ckb,
                                    'reference' => $reference,
                                ], 400);
                            }
                        } else {
                            // Requery failed (network or non-OK). Since ClubKonnect already accepted (100), debit immediately and keep tx pending.
                            DB::transaction(function () use ($user, $amount, $reference, $tx, $body, $convenience, $bundleCode) {
                                $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                $debit = round($amount + $convenience, 2);
                                if ((float)$lockedUser->balance >= (float)$debit) {
                                    $lockedUser->decrement('balance', $debit);

                                    $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                    $tx->update([
                                        'status' => 'pending',
                                        'profit' => $profit,
                                        'provider_response' => $body,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                    ]);

                                    WalletTransaction::create([
                                        'user_id' => $lockedUser->id,
                                        'type' => 'debit',
                                        'amount' => $debit,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                        'source' => 'vtu_data',
                                        'meta' => [
                                            'network' => $tx->network,
                                            'phone_number' => $tx->phone_number,
                                            'bundle_code' => $bundleCode,
                                            'utility_tx_id' => $tx->id,
                                            'convenience_fee' => $convenience,
                                            'status' => 'pending',
                                        ],
                                    ]);
                                } else {
                                    // Not enough funds at this exact moment; keep pending without debit
                                    $tx->update([
                                        'status' => 'pending',
                                        'provider_response' => $body,
                                    ]);
                                }
                            });
                            return response()->json([
                                'message' => 'Order received and processing.',
                                'status' => 'pending',
                                'provider' => $ckRequery['body'] ?? $body,
                                'reference' => $tx->fresh()->reference,
                            ], 200);
                        }
                    } else {
                        // For other non-VTpass providers, do not requery here; allow webhook or later reconciliation
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
                'order_id' => $body['orderid'] ?? ($body['order_id'] ?? ($body['content']['transactions']['order_id'] ?? ($body['content']['transactions']['transactionId'] ?? null))),
                'provider' => $providerUsed ?? 'clubkonnect',
            ]);

            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $debit,
                'reference' => $body['orderid'] ?? ($body['requestId'] ?? $tx->reference),
                'source' => 'vtu_data',
                'meta' => [
                    'network' => $tx->network,
                    'phone_number' => $tx->phone_number,
                    'bundle_code' => $bundleCode,
                    'utility_tx_id' => $tx->id,
                    'provider' => $providerUsed ?? 'clubkonnect',
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
            'reference' => $tx->fresh()->reference,
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
        $cacheKey = 'vtu:data:plans:' . $network;
        $convenience = (float) config('services.vtu.convenience_fee', 0);

        // 1. Determine Primary Provider
        $order = explode(',', config('services.vtu.routing_order', 'clubkonnect,vtpass'));
        $primaryProvider = strtolower(trim($order[0]));

        // 2. Try to fetch from ClubKonnect if it's primary
        if ($primaryProvider === 'clubkonnect') {
            $ckUser = config('services.vtu.clubkonnect.user_id');
            try {
                $r = Http::timeout(15)->get('https://www.nellobytesystems.com/APIDatabundlePlansV2.asp', [
                    'UserID' => $ckUser
                ]);

                $j = $r->json();

                $mobileNetworkData = $j['MOBILE_NETWORK'] ?? null;
                if ($r->ok() && $mobileNetworkData) {
                    $bundles = [];
                    // Find the specific network (e.g., "MTN", "Glo")
                    // We search case-insensitively to match 'mtn' vs 'MTN'
                    $networkKey = null;
                    foreach (array_keys($mobileNetworkData) as $k) {
                        if (strtolower($k) === strtolower($network)) {
                            $networkKey = $k;
                            break;
                        }
                    }

                    if ($networkKey && isset($mobileNetworkData[$networkKey][0]['PRODUCT'])) {
                        $products = $mobileNetworkData[$networkKey][0]['PRODUCT'];

                        foreach ($products as $p) {
                            $bundles[] = [
                                'code' => (string) ($p['PRODUCT_ID'] ?? $p['dataplan_id'] ?? ''),
                                'name' => $p['PRODUCT_NAME'] ?? ($p['name'] ?? ''),
                                'amount' => (float) ($p['PRODUCT_AMOUNT'] ?? ($p['amount'] ?? 0)),
                                'fixed' => true,
                                'convenience_fee' => $convenience,
                                'total_debit' => round((float)($p['PRODUCT_AMOUNT'] ?? ($p['amount'] ?? 0)) + $convenience, 2),
                            ];
                        }
                    }

                    if (!empty($bundles)) {
                        Cache::put($cacheKey, $bundles, now()->addHours(12));
                        return response()->json([
                            'network' => $network,
                            'provider' => 'clubkonnect',
                            'bundles' => $bundles
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error("ClubKonnect Plans Fetch Failed: " . $e->getMessage());
            }
        }

        // 3. Fallback to VTpass
        try {
            $resp = Http::withHeaders([
                'api-key' => config('services.vtu.api_key'),
                'public-key' => config('services.vtu.public_key'),
            ])->get(config('services.vtu.base_url') . '/service-variations', [
                'serviceID' => $serviceId
            ]);

            $json = $resp->json();
            $raw = $json['content']['variations'] ?? [];

            $bundles = array_map(function ($v) use ($convenience) {
                return [
                    'code' => $v['variation_code'], // String ID for VTpass
                    'name' => $v['name'],
                    'amount' => (float) $v['variation_amount'],
                    'fixed' => true,
                    'convenience_fee' => $convenience,
                    'total_debit' => round((float)$v['variation_amount'] + $convenience, 2),
                ];
            }, $raw);

            return response()->json([
                'network' => $network,
                'provider' => 'vtpass',
                'bundles' => $bundles
            ]);

        } catch (\Throwable $e) {
            // 4. Ultimate Fallback (Cache)
            return response()->json([
                'network' => $network,
                'provider' => 'cache',
                'bundles' => Cache::get($cacheKey, []),
                'note' => 'System offline. Showing last known plans.'
            ]);
        }
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
        $convenience = (float) (config('services.vtu.convenience_fee', 0));

        // Use routing order to decide which provider to fetch packages from first.
        $order = array_filter(array_map('trim', explode(',', (string) config('services.vtu.routing_order', 'clubkonnect,shago,vtpass'))));
        $primaryProvider = 'clubkonnect';
        foreach ($order as $p) {
            $p = strtolower($p);
            if ($p === 'clubkonnect') {
                $ck = config('services.vtu.clubkonnect', []);
                if (!empty($ck['enabled']) && !empty($ck['user_id'])) {
                    $primaryProvider = 'clubkonnect';
                    break;
                }
            } elseif ($p === 'vtpass') {
                if ($apiKey && ($publicKey || $secretKey)) {
                    $primaryProvider = 'vtpass';
                    break;
                }
            }
        }

        if ($primaryProvider === 'clubkonnect') {
            $ck = config('services.vtu.clubkonnect', []);
            $ckUser = $ck['user_id'] ?? null;
            $ckBase = rtrim((string)($ck['base_url'] ?? 'https://www.nellobytesystems.com'), '/');
            try {
                $r = Http::timeout(10)
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
                        if (isset($j['content'][$k]) && is_array($j['content'][$k])) { $raw = $j['content'][$k]; break; }
                    }
                    if ($raw === null && isset($j['data']) && is_array($j['data'])) { $raw = $j['data']; }
                    if ($raw === null && isset($j['packages']) && is_array($j['packages'])) { $raw = $j['packages']; }
                    if ($raw === null && array_is_list($j)) { $raw = $j; }

                    $bundles = [];
                    if (is_array($raw)) {
                        foreach ($raw as $p) {
                            $code = (string)($p['package_id'] ?? ($p['code'] ?? ($p['package_code'] ?? ($p['id'] ?? ($p['PRODUCT_ID'] ?? ($p['ID'] ?? ''))))));
                            $name = (string)($p['name'] ?? ($p['description'] ?? ($p['PRODUCT_NAME'] ?? '')));
                            $amount = (float)($p['amount'] ?? ($p['price'] ?? ($p['cost'] ?? ($p['PRODUCT_AMOUNT'] ?? 0))));
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
                Log::warning('ClubKonnect TV packages fetch failed', ['error' => $e->getMessage()]);
            }
        }

        // VTpass fallback
        if ($apiKey && ($publicKey || $secretKey)) {
            $headers = [ 'api-key' => $apiKey ];
            if ($publicKey) { $headers['public-key'] = $publicKey; }
            if ($secretKey) { $headers['secret-key'] = $secretKey; }

            try {
                $resp = Http::withHeaders($headers)
                    ->acceptJson()
                    ->get($baseUrl . '/service-variations', [ 'serviceID' => $service ]);
                $json = $resp->json();
                if ($resp->ok() && is_array($json)) {
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
                            'type' => $v['type'] ?? null,
                        ];
                    }, is_array($raw) ? $raw : []));

                    return response()->json([
                        'service' => $service,
                        'convenience_fee' => $convenience,
                        'bundles' => $bundles,
                        'provider_response' => $json,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('VTU TV variations HTTP error', ['exception' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => 'No VTU provider available for TV bundles'], 502);
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
        $providerUsed = $response['provider_used'] ?? 'clubkonnect';
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
                if (($providerUsed ?? '') === 'vtpass') {
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
                                'message' => 'Processing! Your electricity vend is on the way. Please check your balance in 1 minute.',
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
                    // For ClubKonnect, perform a single immediate requery by RequestID to reduce false pendings
                    if (($providerUsed ?? '') === 'clubkonnect') {
                        $ckRequery = $this->requeryClubKonnectByRequestId($reference);
                        if ($ckRequery['ok']) {
                            $ckb = $ckRequery['body'];
                            if ($this->isVtpassSuccess($ckb)) {
                                $body = $ckb;
                            } elseif ($this->isVtpassPending($ckb)) {
                                DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $ckb, $convenience, $serviceId, $meter, $meterType) {
                                    $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                    if ((float)$lockedUser->balance >= (float)$totalDebit) {
                                        $lockedUser->decrement('balance', $totalDebit);
                                        $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                        $tx->update([
                                            'status' => 'pending',
                                            'profit' => $profit,
                                            'provider_response' => $ckb,
                                            'reference' => $ckb['orderid'] ?? $tx->reference,
                                        ]);
                                        WalletTransaction::create([
                                            'user_id' => $lockedUser->id,
                                            'type' => 'debit',
                                            'amount' => $totalDebit,
                                            'reference' => $ckb['orderid'] ?? $tx->reference,
                                            'source' => 'vtu_electricity',
                                            'meta' => [
                                                'disco' => $serviceId,
                                                'meter_number' => $meter,
                                                'meter_type' => $meterType,
                                                'utility_tx_id' => $tx->id,
                                                'convenience_fee' => $convenience,
                                                'status' => 'pending',
                                            ],
                                        ]);
                                    } else {
                                        $tx->update([
                                            'status' => 'pending',
                                            'provider_response' => $ckb,
                                        ]);
                                    }
                                });
                                return response()->json([
                                    'message' => 'Processing! Your electricity vend is on the way. Please check your balance in 1 minute.',
                                    'status' => 'pending',
                                    'provider' => $ckb,
                                    'reference' => $tx->fresh()->reference,
                                ], 200);
                            } else {
                                $tx->update([
                                    'status' => 'failed',
                                    'provider_response' => $ckb,
                                ]);
                                return response()->json([
                                    'message' => 'Electricity vend failed',
                                    'provider' => $ckb,
                                    'reference' => $reference,
                                ], 400);
                            }
                        } else {
                            DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $body, $convenience, $serviceId, $meter, $meterType) {
                                $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                if ((float)$lockedUser->balance >= (float)$totalDebit) {
                                    $lockedUser->decrement('balance', $totalDebit);
                                    $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                    $tx->update([
                                        'status' => 'pending',
                                        'profit' => $profit,
                                        'provider_response' => $body,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                    ]);
                                    WalletTransaction::create([
                                        'user_id' => $lockedUser->id,
                                        'type' => 'debit',
                                        'amount' => $totalDebit,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                        'source' => 'vtu_electricity',
                                        'meta' => [
                                            'disco' => $serviceId,
                                            'meter_number' => $meter,
                                            'meter_type' => $meterType,
                                            'utility_tx_id' => $tx->id,
                                            'convenience_fee' => $convenience,
                                            'status' => 'pending',
                                        ],
                                    ]);
                                } else {
                                    $tx->update([
                                        'status' => 'pending',
                                        'provider_response' => $body,
                                    ]);
                                }
                            });
                            return response()->json([
                                'message' => 'Order received and processing.',
                                'status' => 'pending',
                                'provider' => $ckRequery['body'] ?? $body,
                                'reference' => $tx->fresh()->reference,
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
                            'reference' => $tx->fresh()->reference,
                        ], 200);
                    }
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
                'reference' => $body['orderid'] ?? $tx->reference,
            ]);
            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $totalDebit,
                'reference' => $body['orderid'] ?? $tx->reference,
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
            $token = $body['metertoken'] ?? ($body['mainToken'] ?? ($body['token'] ?? ($body['purchased_code'] ?? ($body['data']['token'] ?? null))));
            $tokenMsg = $token ? " Token: $token." : "";
            $msg = 'Electricity vend: ₦'.number_format($totalDebit, 2).' to meter '.($meter).' ('.strtoupper($serviceId).').'.$tokenMsg.' Ref: '.$reference.'. Bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {}

        $token = $body['metertoken'] ?? ($body['mainToken'] ?? ($body['token'] ?? ($body['purchased_code'] ?? ($body['data']['token'] ?? null))));
        return response()->json([
            'message' => 'Electricity token vended! ' . ($token ? "Token: $token" : ""),
            'status' => 'success',
            'token' => $token,
            'reference' => $tx->fresh()->reference,
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
        $providerUsed = $response['provider_used'] ?? 'clubkonnect';
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
                if (($providerUsed ?? '') === 'vtpass') {
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
                                'message' => 'Processing! Your cable subscription is on the way. Please check your balance in 1 minute.',
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
                    // For ClubKonnect, perform a single immediate requery by RequestID to reduce false pendings
                    if (($providerUsed ?? '') === 'clubkonnect') {
                        $ckRequery = $this->requeryClubKonnectByRequestId($reference);
                        if ($ckRequery['ok']) {
                            $ckb = $ckRequery['body'];
                            if ($this->isVtpassSuccess($ckb)) {
                                // Treat as success below; set $body to requery body so we persist it
                                $body = $ckb;
                            } elseif ($this->isVtpassPending($ckb)) {
                                // ClubKonnect accepted (100). Debit member immediately and mark pending to protect Coop funds.
                                DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $ckb, $convenience, $service, $smartcard, $bundleCode) {
                                    $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                    if ((float)$lockedUser->balance >= (float)$totalDebit) {
                                        $lockedUser->decrement('balance', $totalDebit);

                                        $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                        $tx->update([
                                            'status' => 'pending',
                                            'profit' => $profit,
                                            'provider_response' => $ckb,
                                            'reference' => $ckb['orderid'] ?? $tx->reference,
                                        ]);

                                        WalletTransaction::create([
                                            'user_id' => $lockedUser->id,
                                            'type' => 'debit',
                                            'amount' => $totalDebit,
                                            'reference' => $ckb['orderid'] ?? $tx->reference,
                                            'source' => 'vtu_cable',
                                            'meta' => [
                                                'service' => $service,
                                                'smartcard_number' => $smartcard,
                                                'bundle_code' => $bundleCode,
                                                'utility_tx_id' => $tx->id,
                                                'convenience_fee' => $convenience,
                                                'status' => 'pending',
                                            ],
                                        ]);
                                    } else {
                                        // Not enough funds at this exact moment; keep pending without debit
                                        $tx->update([
                                            'status' => 'pending',
                                            'provider_response' => $ckb,
                                        ]);
                                    }
                                });
                                return response()->json([
                                    'message' => 'Processing! Your cable subscription is on the way. Please check your balance in 1 minute.',
                                    'status' => 'pending',
                                    'provider' => $ckb,
                                    'reference' => $tx->fresh()->reference,
                                ], 200);
                            } else {
                                // Provider-declared failure after requery
                                $tx->update([
                                    'status' => 'failed',
                                    'provider_response' => $ckb,
                                ]);
                                return response()->json([
                                    'message' => 'Cable subscription failed',
                                    'provider' => $ckb,
                                    'reference' => $reference,
                                ], 400);
                            }
                        } else {
                            // Requery failed (network or non-OK). Since ClubKonnect already accepted (100), debit immediately and keep tx pending.
                            DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $body, $convenience, $service, $smartcard, $bundleCode) {
                                $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
                                if ((float)$lockedUser->balance >= (float)$totalDebit) {
                                    $lockedUser->decrement('balance', $totalDebit);

                                    $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
                                    $tx->update([
                                        'status' => 'pending',
                                        'profit' => $profit,
                                        'provider_response' => $body,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                    ]);

                                    WalletTransaction::create([
                                        'user_id' => $lockedUser->id,
                                        'type' => 'debit',
                                        'amount' => $totalDebit,
                                        'reference' => $body['orderid'] ?? $tx->reference,
                                        'source' => 'vtu_cable',
                                        'meta' => [
                                            'service' => $service,
                                            'smartcard_number' => $smartcard,
                                            'bundle_code' => $bundleCode,
                                            'utility_tx_id' => $tx->id,
                                            'convenience_fee' => $convenience,
                                            'status' => 'pending',
                                        ],
                                    ]);
                                } else {
                                    // Not enough funds at this exact moment; keep pending without debit
                                    $tx->update([
                                        'status' => 'pending',
                                        'provider_response' => $body,
                                    ]);
                                }
                            });
                            return response()->json([
                                'message' => 'Order received and processing.',
                                'status' => 'pending',
                                'provider' => $ckRequery['body'] ?? $body,
                                'reference' => $tx->fresh()->reference,
                            ], 200);
                        }
                    } else {
                        // For other non-VTpass providers, do not requery here; allow webhook or later reconciliation
                        $tx->update([
                            'status' => 'pending',
                            'provider_response' => $body,
                        ]);
                        return response()->json([
                            'message' => 'Cable subscription is processing with provider. Check history for final status shortly.',
                            'status' => 'pending',
                            'provider' => $body,
                            'reference' => $tx->fresh()->reference,
                        ], 200);
                    }
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

        $insufficient4 = false;
        DB::transaction(function () use ($user, $totalDebit, $reference, $tx, $body, $convenience, $service, $smartcard, $bundleCode, &$insufficient4) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
            if ((float)$lockedUser->balance < (float)$totalDebit) {
                $tx->update([
                    'status' => 'pending',
                    'provider_response' => $body,
                ]);
                $insufficient4 = true;
                return;
            }

            $lockedUser->decrement('balance', $totalDebit);
            $profit = round(((float)$tx->amount - (float)$tx->cost_price), 2);
            $tx->update([
                'status' => 'success',
                'profit' => $profit,
                'provider_response' => $body,
                'reference' => $body['orderid'] ?? $tx->reference,
            ]);
            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $totalDebit,
                'reference' => $body['orderid'] ?? $tx->reference,
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

        if ($insufficient4) {
            $user->refresh();
            return response()->json([
                'message' => 'Cable subscription is processing. Wallet will be debited when funds are available.',
                'status' => 'pending',
                'reference' => $reference,
                'balance' => (float)$user->balance,
                'transaction' => $tx->fresh(),
            ], 202);
        }

        $user->refresh();
        try {
            $sms = app(\App\Services\SmsService::class);
            $msg = 'Cable subscription: ₦'.number_format($totalDebit, 2).' for '.strtoupper($service).' ('.($bundleCode).') on '.($smartcard).'. Ref: '.$reference.'. Bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {}

        return response()->json([
            'message' => 'Cable subscribed!',
            'status' => 'success',
            'reference' => $tx->fresh()->reference,
            'balance' => (float)$user->balance,
            'transaction' => $tx->fresh(),
        ]);
    }

    public function verifyMerchant(Request $request)
    {
        $validated = $request->validate([
            'serviceID' => 'required',
            'billersCode' => 'required',
            'type' => 'nullable', // required for cable/electricity
        ]);

        $type = (string) $request->input('type', 'prepaid');
        $serviceId = (string) $request->input('serviceID');
        $billersCode = (string) $request->input('billersCode');

        // Determine if it's cable or electricity for the router
        $vtuType = 'verify-electricity';
        $cableServices = ['dstv', 'gotv', 'startimes', 'showmax'];
        if (in_array(strtolower($serviceId), $cableServices)) {
            $vtuType = 'verify-cable';
        }

        $payload = [
            'serviceID' => $serviceId,
            'billersCode' => $billersCode,
            'type' => $type,
        ];

        $response = $this->callVtuSmart($vtuType, $payload);

        if (!$response['ok']) {
            $msg = $response['body']['message'] ?? $response['body']['response_description'] ?? $response['error'] ?? 'Verification failed';
            return response()->json([
                'message' => $msg,
                'details' => $response['body'] ?? $response['error']
            ], 422);
        }

        $body = $response['body'];
        // Nellobyte returns { customer_name: "..." }
        // VTpass returns { content: { Customer_Name: "..." } }
        $customerName = $body['customer_name'] ?? $body['Customer_Name'] ?? $body['customername'] ?? $body['content']['Customer_Name'] ?? $body['content']['customer_name'] ?? null;

        if (!$customerName ||
            str_contains(strtoupper($customerName), 'INVALID') ||
            str_contains(strtoupper($customerName), 'NOT FOUND') ||
            strtoupper(trim($customerName)) === 'N/A'
        ) {
            $errorMsg = $body['message'] ?? $body['response_description'] ?? $body['content']['error'] ?? $body['customer_name'] ?? $body['Customer_Name'] ?? 'Verification failed';
            if ($errorMsg === 'Verification failed' && $customerName && $customerName !== 'N/A') {
                $errorMsg = $customerName;
            }

            return response()->json([
                'message' => $errorMsg,
                'details' => $body
            ], 422);
        }

        return response()->json([
            'customer_name' => $customerName,
            'status' => 'success',
            'provider_response' => $body
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
                $resp = $this->callVtpass($type, $payload);
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
            $mapAirtime = [ 'mtn' => '01', 'glo' => '02', '9mobile' => '03', 'airtel' => '04' ];
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
            $network = strtolower((string)($payload['network'] ?? ''));
            if (!$network && $serviceId) {
                $network = (str_contains($serviceId, '-')) ? explode('-', $serviceId)[0] : $serviceId;
            }
            if ($network === 'etisalat') { $network = '9mobile'; }
            $mapData = [ 'mtn' => '01',
                'glo' => '02',
                '9mobile' => '03',
                'airtel' => '04'
            ];
            $mobileNetwork = $mapData[$network] ?? null;

            $dataPlan = $payload['variation_code'] ?? ($payload['DataPlan'] ?? $payload['bundle_code'] ?? null);
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
            $mapCable = [ 'dstv' => '01', 'gotv' => '02', 'startimes' => '03' ];
            $cableCode = $mapCable[$service] ?? $service;

            $package = $payload['variation_code'] ?? ($payload['Package'] ?? null);
            $smartcard = $payload['billersCode'] ?? ($payload['SmartCardNo'] ?? null);
            $phone = $payload['phone'] ?? ($payload['PhoneNo'] ?? null);
            if (!$service || !$package || !$smartcard || !$requestId) {
                return [ 'ok' => false, 'error' => 'Missing required fields', 'body' => [ 'note' => 'service/package/smartcard/request_id required' ], 'status' => 0 ];
            }

            $endpoint = '/APICableTVV1.asp';
            $params = array_merge($params, [
                'CableTV' => $cableCode,
                'Package' => $package,
                'SmartCardNo' => $smartcard,
                'RequestID' => $requestId,
            ]);
            if (!empty($phone)) { $params['PhoneNo'] = $phone; }
            if ($cb !== '') { $params['CallBackURL'] = $cb; }
        } elseif ($type === 'electricity') {
            // Electricity purchase via APIElectricityV1.asp
            $service = strtolower((string)($payload['serviceID'] ?? ''));
            $mapDisco = [
                'eko-electric' => '01', 'ekedc' => '01',
                'ikeja-electric' => '02', 'ikedc' => '02',
                'abuja-electric' => '03', 'aedc' => '03',
                'kano-electric' => '04', 'kedco' => '04',
                'port-harcourt-electric' => '05', 'phed' => '05',
                'jos-electric' => '06', 'jed' => '06',
                'kaduna-electric' => '07', 'kaedco' => '07',
                'ibadan-electric' => '08', 'ibedc' => '08',
                'enugu-electric' => '09', 'eedc' => '09',
                'benin-electric' => '10', 'bedc' => '10',
                'yola-electric' => '11', 'yedc' => '11',
            ];
            $discoCode = $mapDisco[$service] ?? null;
            $meterNo = $payload['billersCode'] ?? ($payload['MeterNo'] ?? null);
            $meterType = strtolower((string)($payload['variation_code'] ?? 'prepaid')) === 'postpaid' ? '02' : '01';
            $amount = $payload['amount'] ?? null;
            $phone = $payload['phone'] ?? ($payload['PhoneNo'] ?? null);

            if (!$discoCode || !$meterNo || !$amount || !$requestId) {
                return [ 'ok' => false, 'error' => 'Missing required fields', 'body' => [ 'note' => 'disco/meterno/amount/request_id required' ], 'status' => 0 ];
            }

            $endpoint = '/APIElectricityV1.asp';
            $params = array_merge($params, [
                'ElectricCompany' => $discoCode,
                'MeterType' => $meterType,
                'MeterNo' => $meterNo,
                'Amount' => $amount,
                'RequestID' => $requestId,
            ]);
            if (!empty($phone)) { $params['PhoneNo'] = $phone; }
            if ($cb !== '') { $params['CallBackURL'] = $cb; }
        } elseif ($type === 'verify-cable' || $type === 'verify-electricity') {
            $service = strtolower((string)($payload['serviceID'] ?? ''));
            $billersCode = $payload['billersCode'] ?? null;

            if ($type === 'verify-cable') {
                $mapCable = [ 'dstv' => '01', 'gotv' => '02', 'startimes' => '03' ];
                $cableCode = $mapCable[$service] ?? $service;

                $endpoint = '/APIVerifyCableTVV1.0.asp';
                $params = array_merge($params, [
                    'CableTV' => $cableCode,
                    'SmartCardNo' => $billersCode,
                ]);
            } else {
                $mapDisco = [
                    'eko-electric' => '01', 'ekedc' => '01',
                    'ikeja-electric' => '02', 'ikedc' => '02',
                    'abuja-electric' => '03', 'aedc' => '03',
                    'kano-electric' => '04', 'kedco' => '04',
                    'port-harcourt-electric' => '05', 'phed' => '05',
                    'jos-electric' => '06', 'jed' => '06', 'jedc' => '06',
                    'kaduna-electric' => '07', 'kaedco' => '07',
                    'ibadan-electric' => '08', 'ibedc' => '08',
                    'enugu-electric' => '09', 'eedc' => '09',
                    'benin-electric' => '10', 'bedc' => '10',
                    'yola-electric' => '11', 'yedc' => '11',
                ];
                $discoCode = $mapDisco[$service] ?? null;
                $meterType = strtolower((string)($payload['type'] ?? 'prepaid')) === 'postpaid' ? '02' : '01';

                $endpoint = '/APIVerifyElectricityV1.asp';
                $params = array_merge($params, [
                    'ElectricCompany' => $discoCode,
                    'MeterNo' => $billersCode,
                    'MeterType' => $meterType,
                ]);
            }
        } elseif ($type === 'cancel') {
            $orderId = $payload['order_id'] ?? ($payload['OrderID'] ?? null);
            if (!$orderId) {
                return [ 'ok' => false, 'error' => 'OrderID required for cancellation', 'body' => null, 'status' => 0 ];
            }
            $endpoint = '/APICancelV1.asp';
            $params = array_merge($params, [
                'OrderID' => $orderId,
            ]);
        } else {
            return [ 'ok' => false, 'error' => 'Unsupported channel', 'body' => null, 'status' => 0 ];
        }

        $status = 0; $ok = false; $bodyOut = null; $error = null;
        try {
            // Use GET for all ClubKonnect/Nellobytes endpoints as per documentation.
            // Removing acceptJson() as some older endpoints might fail with standard JSON headers.
            $resp = Http::timeout(15)
                ->get($baseUrl . $endpoint, $params);
            $status = $resp->status();
            $body = $resp->body();
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('ClubKonnect bad response', ['status' => $status, 'body' => $body, 'endpoint' => $endpoint]);
                return [ 'ok' => false, 'error' => 'Bad response', 'body' => (is_array($json)?$json:['raw' => $body]), 'status' => $status ];
            }
            // Pass through JSON; success/pending are detected by isVtpassSuccess/isVtpassPending via statuscode/orderstatus
            $bodyOut = is_array($json) ? $json : [ 'raw' => $body ];
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

    private function callVtpass(string $type, array $payload): array
    {
        $serviceID = strtolower((string)($payload['serviceID'] ?? ''));
        $mapping = [
            'ekedc' => 'eko-electric',
            'ikedc' => 'ikeja-electric',
            'aedc' => 'abuja-electric',
            'kedco' => 'kano-electric',
            'phed' => 'port-harcourt-electric',
            'jed' => 'jos-electric',
            'jedc' => 'jos-electric',
            'kaedco' => 'kaduna-electric',
            'ibedc' => 'ibadan-electric',
            'eedc' => 'enugu-electric',
            'bedc' => 'benin-electric',
            'yedc' => 'yola-electric',
        ];
        if (isset($mapping[$serviceID])) {
            $payload['serviceID'] = $mapping[$serviceID];
        }

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

        $headers = [ 'api-key' => $apiKey ];
        if ($publicKey) { $headers['public-key'] = $publicKey; }
        if ($secretKey) { $headers['secret-key'] = $secretKey; }

        $endpoint = '/pay';
        if ($type === 'verify-cable' || $type === 'verify-electricity') {
            $endpoint = '/merchant-verify';
        }

        try {
            $resp = Http::withHeaders($headers)
                ->acceptJson()
                ->post($baseUrl . $endpoint, $payload);
        } catch (\Throwable $e) {
            Log::error('VTU provider HTTP error', ['exception' => $e->getMessage(), 'endpoint' => $endpoint]);
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
            return [ 'ok' => false, 'error' => 'Network error', 'body' => null, 'status' => 0 ];
        }

        $json = $resp->json();
        if (!$resp->ok()) {
            Log::error('VTU provider responded with error', ['status' => $resp->status(), 'body' => $json, 'endpoint' => $endpoint]);
            if (app()->bound('sentry')) {
                app('sentry')->captureMessage('VTPass API Error: ' . ($json['message'] ?? 'Unknown'), \Sentry\Severity::error());
            }
            return [ 'ok' => false, 'error' => 'Bad response', 'body' => $json, 'status' => $resp->status() ];
        }

        return [ 'ok' => true, 'body' => $json, 'status' => $resp->status() ];
    }

    private function isVtpassSuccess($body): bool
    {
        if (is_array($body)) {
            // Verification responses (electricity/cable)
            $cname = strtoupper(trim((string)($body['customer_name'] ?? $body['Customer_Name'] ?? $body['customername'] ?? $body['content']['Customer_Name'] ?? $body['content']['customer_name'] ?? '')));
            if ($cname !== '' && ($cname === 'N/A' || str_contains($cname, 'INVALID') || str_contains($cname, 'NOT FOUND'))) {
                return false;
            }

            // VTpass standard success code
            $code = (string)($body['code'] ?? ($body['data']['code'] ?? ''));
            if ($code === '000') return true;

            // Nellobytes/ClubKonnect success: statuscode=200 (ORDER_COMPLETED)
            $ckCode = (string)($body['statuscode'] ?? ($body['status_code'] ?? ($body['StatusCode'] ?? ($body['status'] ?? ''))));
            if (in_array($ckCode, ['200', 'OK', '201', '000', 'ORDER_COMPLETED'])) { // be literal
                return true;
            }
            $orderStatusUp = strtoupper((string)($body['orderstatus'] ?? ($body['order_status'] ?? ($body['OrderStatus'] ?? ''))));
            if (in_array($orderStatusUp, ['ORDER_COMPLETED', 'COMPLETED', 'SUCCESS'])) {
                return true;
            }

            // If a non-empty, non-invalid name is present, consider it success
            if ($cname !== '') {
                return true;
            }

            // 2. Check for "success" or "successful" or "delivered" strings
            $status = strtolower((string)($body['status'] ?? ''));
            $respDesc = strtolower((string)($body['response_description'] ?? ''));
            $message = strtolower((string)($body['message'] ?? ''));

            if (in_array($status, ['success', 'successful', 'delivered', 'completed', 'order_completed'])) {
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
        } elseif (is_string($body)) {
            $b = strtoupper(trim($body));
            return in_array($b, ['ORDER_COMPLETED', 'SUCCESS', 'OK', 'COMPLETED', 'ORDER_RECEIVED', 'RECEIVED', 'SUCCESSFUL']);
        }
        return false;
    }

    private function isVtpassPending($body): bool
    {
        if (is_array($body)) {
            $status = strtolower((string)($body['status'] ?? ''));
            if (in_array($status, ['pending', 'processing', 'initiated', 'queued', 'order_received', 'order_onhold'])) { return true; }
            $txStatus = strtolower((string)($body['data']['transactions']['status'] ?? ($body['content']['transactions']['status'] ?? ($body['transactions']['status'] ?? ''))));
            if (in_array($txStatus, ['pending', 'processing', 'initiated', 'queued'])) { return true; }
            // Nellobytes/ClubKonnect pending fields
            $ckCode = (string)($body['statuscode'] ?? ($body['status_code'] ?? ($body['StatusCode'] ?? ($body['status'] ?? ''))));
            if (in_array($ckCode, ['100', 'ORDER_RECEIVED', 'RECEIVED', 'ORDER_ONHOLD', 'ONHOLD', 'PENDING', 'PROCESSING'])) { return true; }
            $orderStatusUp = strtoupper((string)($body['orderstatus'] ?? ($body['order_status'] ?? '')));
            if (in_array($orderStatusUp, ['ORDER_RECEIVED', 'RECEIVED', 'ORDER_ONHOLD', 'ONHOLD'])) { return true; }
            $desc = strtolower((string)($body['response_description'] ?? ($body['message'] ?? '')));
            if ($desc && (str_contains($desc, 'pending') || str_contains($desc, 'processing') || str_contains($desc, 'initiated') || str_contains($desc, 'queue'))) { return true; }
            // Some VTpass variants use non-000 codes while processing
            $code = (string)($body['code'] ?? ($body['data']['code'] ?? ''));
            if (in_array($code, ['016', '099'])) { return true; }
        } elseif (is_string($body)) {
            $b = strtoupper(trim($body));
            return in_array($b, ['ORDER_RECEIVED', 'ORDER_ONHOLD', 'PENDING', 'PROCESSING']);
        }
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
                ->post($baseUrl . '/requery', [ 'request_id' => $reference ]);
        } catch (\Throwable $e) {
            Log::error('VTU requery HTTP error', ['exception' => $e->getMessage()]);
            return [ 'ok' => false, 'error' => 'Network error', 'body' => null, 'status' => 0 ];
        }

        $json = $resp->json();
        if (!$resp->ok()) {
            if ($resp->status() >= 500) {
                Log::error('VTU requery server error', ['status' => $resp->status(), 'body' => $json]);
            } else {
                Log::warning('VTU requery non-success response', ['status' => $resp->status(), 'body' => $json]);
            }
            return [ 'ok' => false, 'error' => 'Bad response', 'body' => $json, 'status' => $resp->status() ];
        }

        return [ 'ok' => true, 'body' => $json, 'status' => $resp->status() ];
    }

    private function requeryClubKonnect(array $params): array
    {
        $cfg = config('services.vtu.clubkonnect', []);
        $enabled = (bool)($cfg['enabled'] ?? false);
        $userId = $cfg['user_id'] ?? null;
        $apiKey = $cfg['api_key'] ?? null;
        $baseUrl = rtrim((string)($cfg['base_url'] ?? 'https://www.nellobytesystems.com'), '/');
        if (!$enabled || !$userId || !$apiKey) {
            return [ 'ok' => false, 'error' => 'Provider not configured', 'body' => null, 'status' => 0 ];
        }

        try {
            // Removing acceptJson() for Nellobytes compatibility
            $resp = Http::timeout(15)
                ->get($baseUrl . '/APIQueryV1.asp', array_merge([
                    'UserID' => $userId,
                    'APIKey' => $apiKey,
                ], $params));
            $status = $resp->status();
            $body = $resp->body();
            $json = $resp->json();
            if (!$resp->ok()) {
                Log::warning('ClubKonnect requery bad response', ['status' => $status, 'body' => $body]);
                return [ 'ok' => false, 'error' => 'Bad response', 'body' => (is_array($json)?$json:['raw'=>$body]), 'status' => $status ];
            }
            return [ 'ok' => true, 'body' => (is_array($json)?$json:['raw'=>$body]), 'status' => $status ];
        } catch (\Throwable $e) {
            Log::error('ClubKonnect requery HTTP error', ['error' => $e->getMessage()]);
            return [ 'ok' => false, 'error' => 'Network error', 'body' => null, 'status' => 0 ];
        }
    }

    private function requeryClubKonnectByRequestId(string $requestId): array
    {
        return $this->requeryClubKonnect(['RequestID' => $requestId]);
    }

    private function requeryClubKonnectByOrderId(string $orderId): array
    {
        return $this->requeryClubKonnect(['OrderID' => $orderId]);
    }

    private function cancelClubKonnectByOrderId(string $orderId): array
    {
        return $this->callClubKonnect('cancel', ['OrderID' => $orderId]);
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
