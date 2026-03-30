<?php

namespace App\Http\Controllers\Api;

use App\Notifications\PaymentNotification;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ProjectInvestment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RepaymentReceiptUser;
use App\Mail\PaymentStatusMail;
use App\Services\SmsService;

class WebhookController extends Controller
{
    public function handlePaystack(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $secret = config('services.paystack.secret_key');

        if (! $signature || ($signature !== hash_hmac('sha512', $request->getContent(), (string) $secret))) {
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        // Handle immediate failure notifications
        if ($event === 'charge.failed') {
            $reference = $data['reference'] ?? null;
            $amountNgn = isset($data['amount']) ? round(((int) $data['amount']) / 100, 2) : null;
            $channel = $data['channel'] ?? null;
            $reason = $data['gateway_response'] ?? ($data['message'] ?? 'Payment failed');
            $meta = $data['metadata'] ?? [];
            if (is_string($meta)) { $decoded = json_decode($meta, true); if (json_last_error() === JSON_ERROR_NONE) { $meta = $decoded; } }
            if (is_object($meta)) { $meta = (array) $meta; }
            $user = null;
            if ($reference) {
                $contrib = \App\Models\Contribution::where('reference', $reference)->first();
                if ($contrib) { $user = \App\Models\User::find($contrib->user_id); }
            }
            if (!$user) {
                $customerCode = $data['customer']['customer_code'] ?? null;
                if ($customerCode) { $user = \App\Models\User::where('paystack_customer_code', $customerCode)->first(); }
                if (!$user && isset($meta['user_id'])) { $uid = is_numeric($meta['user_id']) ? (int)$meta['user_id'] : null; if ($uid) { $user = \App\Models\User::find($uid); } }
            }
            if ($user) {
                try {
                    if (!empty($user->email)) {
                        Mail::to($user->email)->send(new PaymentStatusMail(
                            status: 'failed',
                            title: 'Payment Failed',
                            message: 'Your payment attempt was not successful. ' . $reason,
                            amount: $amountNgn,
                            reference: $reference,
                            channel: $channel,
                            route: $reference && \App\Models\Contribution::where('reference', $reference)->exists() ? '/pay' : '/wallet',
                            meta: ['provider' => 'paystack']
                        ));
                    }
                    $push = app(\App\Services\PushService::class);
                    $token = $user->fcm_token ?: ($user->device_token ?? null);
                    $push->send($token, 'Payment Failed', 'Your payment attempt was not successful. ' . $reason, [
                        'type' => 'payment_failed',
                        'amount' => $amountNgn,
                        'reference' => (string) ($reference ?? ''),
                        'route' => $reference && \App\Models\Contribution::where('reference', $reference)->exists() ? '/pay' : '/wallet',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send Paystack failure notification', ['reference' => $reference, 'error' => $e->getMessage()]);
                }
            }
            return response()->json(['status' => 'ok']);
        }

        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;
            if (! $reference) {
                return response()->json(['message' => 'No reference'], 400);
            }

            // Verify transaction with Paystack for extra safety
            $verify = Http::withToken($secret)
                ->acceptJson()
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(3, 300)
                ->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));

            if (! $verify->ok() || ($verify->json('status') !== true)) {
                Log::warning('Paystack verify call failed', ['reference' => $reference, 'body' => $verify->json()]);
                return response()->json(['message' => 'Verification failed'], 400);
            }

            $vd = $verify->json('data');
            if (! $vd || ($vd['status'] ?? null) !== 'success') {
                Log::info('Paystack verify not successful', ['reference' => $reference, 'status' => $vd['status'] ?? null]);
                return response()->json(['message' => 'Not successful'], 200);
            }

            // Sum expected amount from pending contributions
            $contributions = Contribution::where('reference', $reference)
                ->where('status', 'pending')
                ->get();

            if ($contributions->isEmpty()) {
                // First, check if this is a pending loan repayment reference
                $loanRep = QardHasanRepayment::where('reference', $reference)->first();
                if ($loanRep) {
                    $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);
                    $paidCurrency = $vd['currency'] ?? 'NGN';
                    if ($paidCurrency !== 'NGN' || ($amountNgn + 0.005) < (float) $loanRep->amount) {
                        Log::warning('Paystack webhook: amount/currency mismatch for loan repayment', [
                            'reference' => $reference,
                            'paid_amount' => $amountNgn,
                            'expected' => (float) $loanRep->amount,
                            'currency' => $paidCurrency,
                        ]);
                        return response()->json(['message' => 'Amount mismatch'], 400);
                    }

                    if ($loanRep->status === 'success') {
                        return response()->json(['status' => 'ok']);
                    }

                    DB::transaction(function () use ($loanRep) {
                        $loan = QardHasan::lockForUpdate()->find($loanRep->qard_hasan_id);
                        if ($loan) {
                            $loanRep->status = 'success';
                            $loanRep->paid_at = now();
                            $loanRep->save();

                            $loan->paid_amount = (float) $loan->paid_amount + (float) $loanRep->amount;
                            if ($loan->paid_amount >= $loan->principal_amount) {
                                $loan->status = 'completed';
                            }
                            $loan->save();
                        } else {
                            // If loan missing, mark repayment as success to avoid repeated retries (but log)
                            $loanRep->status = 'success';
                            $loanRep->paid_at = now();
                            $loanRep->save();
                            Log::warning('Loan not found when finalizing loan repayment from Paystack', [
                                'repayment_id' => $loanRep->id,
                                'qard_hasan_id' => $loanRep->qard_hasan_id,
                            ]);
                        }
                    });

                    // Send repayment receipt to user (best-effort)
                    try {
                        $loanRep->refresh();
                        $loan = QardHasan::with('user')->find($loanRep->qard_hasan_id);
                        if ($loan && $loan->user && !empty($loan->user->email)) {
                            Mail::to($loan->user->email)->send(new RepaymentReceiptUser($loan, $loanRep));
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send repayment receipt email (paystack webhook)', [
                            'repayment_id' => $loanRep->id,
                            'loan_id' => $loanRep->qard_hasan_id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Best-effort SMS + Push notification to member
                    try {
                        $loan = QardHasan::with('user')->find($loanRep->qard_hasan_id);
                        if ($loan && $loan->user) {
                            $remaining = max(0, (float) $loan->principal_amount - (float) $loan->paid_amount);
                            $sms = app(\App\Services\SmsService::class);
                            $push = app(\App\Services\PushService::class);
                            $msg = 'Loan repayment received: ₦'.number_format((float)$loanRep->amount, 2).' for '.($loan->qard_id_string).'. Remaining: ₦'.number_format($remaining, 2).'. Ref: '.($loanRep->reference);
                            $sms->send($loan->user->phone ?? null, $msg);
                            $token = $loan->user->fcm_token ?: ($loan->user->device_token ?? null);
                            $push->send($token, 'Repayment Received', $msg, [
                                'type' => 'loan_repayment',
                                'loan_id' => $loan->id,
                                'qard_id_string' => $loan->qard_id_string,
                                'amount' => (float) $loanRep->amount,
                                'reference' => (string) $loanRep->reference,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        // ignore notification errors
                    }

                    return response()->json(['status' => 'success']);
                }

                // No pending contributions found for this reference.
                // This could be a Dedicated Virtual Account (DVA) bank transfer top-up.
                $vdChannel = $vd['channel'] ?? null; // e.g., "bank_transfer", "card"
                $customerCode = $vd['customer']['customer_code'] ?? null;
                $receiverAccount = $vd['authorization']['receiver_bank_account_number'] ?? ($vd['authorization']['account_number'] ?? null);

                // Normalize metadata from Paystack (can be array, object, or JSON string)
                $rawMeta = $vd['metadata'] ?? null;
                $metadata = null;
                if (is_array($rawMeta)) {
                    $metadata = $rawMeta;
                } elseif (is_string($rawMeta)) {
                    $decoded = json_decode($rawMeta, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $metadata = $decoded;
                    }
                } elseif (is_object($rawMeta)) {
                    $metadata = (array) $rawMeta;
                }
                if (! $metadata) {
                    $rm = $request->input('data.metadata');
                    if (is_array($rm)) {
                        $metadata = $rm;
                    } elseif (is_string($rm)) {
                        $decoded = json_decode($rm, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $metadata = $decoded;
                        }
                    } elseif (is_object($rm)) {
                        $metadata = (array) $rm;
                    }
                }

                $metaUserId = $metadata['user_id'] ?? null;
                if (is_string($metaUserId) && ctype_digit($metaUserId)) {
                    $metaUserId = (int) $metaUserId;
                }

                $topupUser = null;
                if ($customerCode) {
                    $topupUser = User::where('paystack_customer_code', $customerCode)->first();
                }
                if (! $topupUser && $receiverAccount) {
                    $topupUser = User::where('dva_account_number', $receiverAccount)->first();
                }
                if (! $topupUser && $metaUserId) {
                    $topupUser = User::find($metaUserId);
                }

                if (! $topupUser) {
                    Log::info('Paystack webhook: reference has no contributions and no matching user', [
                        'reference' => $reference,
                        'customer_code' => $customerCode,
                        'receiver_account' => $receiverAccount,
                        'metadata_present' => (bool) $metadata,
                        'metadata_user_id' => $metaUserId,
                        'channel' => $vdChannel,
                    ]);
                    return response()->json(['status' => 'ignored']);
                }

                // Amount in Naira
                $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);
                $currency = $vd['currency'] ?? 'NGN';
                if ($currency !== 'NGN' || $amountNgn <= 0) {
                    Log::warning('Paystack webhook: invalid currency/amount for wallet topup', [
                        'reference' => $reference,
                        'amount_ngn' => $amountNgn,
                        'currency' => $currency,
                    ]);
                    return response()->json(['status' => 'ignored']);
                }

                // Idempotency: if we've already recorded this reference as a wallet transaction, skip
                if (WalletTransaction::where('reference', $reference)->exists()) {
                    return response()->json(['status' => 'ok']);
                }

                DB::transaction(function () use ($topupUser, $amountNgn, $reference, $vdChannel, $vd, $customerCode) {
                    // Persist Paystack customer code for future lookups if missing
                    if (empty($topupUser->paystack_customer_code) && !empty($customerCode)) {
                        $topupUser->paystack_customer_code = $customerCode;
                        $topupUser->save();
                    }

                    // Credit wallet
                    $topupUser->increment('balance', $amountNgn);

                    // Record wallet credit transaction
                    WalletTransaction::create([
                        'user_id' => $topupUser->id,
                        'type' => 'credit',
                        'amount' => $amountNgn,
                        'reference' => $reference,
                        'source' => $vdChannel === 'bank_transfer' ? 'paystack_dva' : 'paystack_charge',
                        'meta' => [
                            'channel' => $vdChannel,
                            'customer_code' => $vd['customer']['customer_code'] ?? null,
                            'receiver_account' => $vd['authorization']['receiver_bank_account_number'] ?? ($vd['authorization']['account_number'] ?? null),
                        ],
                    ]);
                });

                Log::info('Paystack wallet top-up processed', [
                    'reference' => $reference,
                    'user_id' => $topupUser->id,
                    'channel' => $vdChannel,
                ]);

                // Notify user (non-blocking) + best-effort Push
                try {
                    $topupUser->notify(new PaymentNotification(
                        title: 'Wallet Top-up Successful',
                        message: 'Your wallet has been credited successfully.',
                        amount: $amountNgn,
                        reference: $reference,
                        source: 'wallet_topup'
                    ));

                    // Email receipt (best-effort)
                    try {
                        if (!empty($topupUser->email)) {
                            Mail::to($topupUser->email)->send(new PaymentStatusMail(
                                status: 'success',
                                title: 'Wallet Top-up Successful',
                                message: 'Your wallet has been credited successfully.',
                                amount: $amountNgn,
                                reference: $reference,
                                channel: $vdChannel,
                                route: '/wallet',
                                meta: ['provider' => 'paystack']
                            ));
                        }
                    } catch (\Throwable $e) {}

                    // Fire push notification to device
                    $push = app(\App\Services\PushService::class);
                    $fresh = $topupUser->fresh();
                    $newBal = (float) ($fresh->balance ?? 0);
                    $token = $fresh->fcm_token ?: ($fresh->device_token ?? null);
                    $push->send($token, 'Wallet Top-up Successful', 'Your wallet has been credited successfully.', [
                        'type' => 'wallet_topup',
                        'amount' => (float) $amountNgn,
                        'reference' => (string) $reference,
                        'balance' => $newBal,
                        'route' => '/wallet',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send wallet top-up notification', [
                        'reference' => $reference,
                        'user_id' => $topupUser->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Best-effort SMS notification
                try {
                    $fresh = $topupUser->fresh();
                    $newBal = (float) ($fresh->balance ?? 0);
                    $sms = app(\App\Services\SmsService::class);
                    $msg = 'Wallet top-up: ₦'.number_format($amountNgn, 2).". New bal: ₦".number_format($newBal, 2).'. Ref: '.$reference;
                    $sms->send($fresh->phone ?? null, $msg);
                } catch (\Throwable $e) {
                    // ignore SMS errors
                }

                return response()->json(['status' => 'success']);
            }

            $expectedTotal = (float) $contributions->sum('amount');
            $paidAmountKobo = (int) ($vd['amount'] ?? 0); // in kobo
            $paidCurrency = $vd['currency'] ?? 'NGN';

            if ($paidCurrency !== 'NGN' || $paidAmountKobo < (int) round($expectedTotal * 100)) {
                Log::warning('Paystack amount/currency mismatch', [
                    'reference' => $reference,
                    'expected' => $expectedTotal,
                    'paid_kobo' => $paidAmountKobo,
                    'currency' => $paidCurrency,
                ]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

            $user = User::find($contributions->first()->user_id);

            foreach ($contributions as $contribution) {
                $contribution->status = 'success';
                $contribution->save();
            }

            // Notify user (non-blocking) + best-effort Push
            try {
                if ($user) {
                    $user->notify(new PaymentNotification(
                        title: 'Payment Successful',
                        message: 'Your payment has been received and allocated to your schemes.',
                        amount: $expectedTotal,
                        reference: $reference,
                        source: 'scheme_payment'
                    ));

                    // Email receipt (best-effort)
                    try {
                        if (!empty($user->email)) {
                            Mail::to($user->email)->send(new PaymentStatusMail(
                                status: 'success',
                                title: 'Payment Successful',
                                message: 'Your payment has been received and allocated to your schemes.',
                                amount: $expectedTotal,
                                reference: $reference,
                                channel: $vd['channel'] ?? null,
                                route: '/passbook',
                                meta: ['provider' => 'paystack']
                            ));
                        }
                    } catch (\Throwable $e) {}

                    // Fire push notification to device
                    $push = app(\App\Services\PushService::class);
                    $token = $user->fcm_token ?: ($user->device_token ?? null);
                    $push->send($token, 'Payment Successful', 'Your payment has been received and allocated to your schemes.', [
                        'type' => 'scheme_payment',
                        'amount' => (float) $expectedTotal,
                        'reference' => (string) $reference,
                        'route' => '/passbook',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send scheme payment notification', [
                    'reference' => $reference,
                    'user_id' => optional($user)->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Do not credit wallet here. Contributions were paid directly to schemes via this reference.
            // Wallet top-ups are handled in the branch above when no pending contributions exist.

            Log::info('Paystack payment processed', ['reference' => $reference, 'user_id' => optional($user)->id]);
        }

        return response()->json(['status' => 'success']);
    }

    public function handleFlutterwave(Request $request)
    {
        // Verify webhook signature using FLW_SECRET_HASH
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.secret_hash');
        if (!$secretHash || !$signature || !hash_equals((string)$secretHash, (string)$signature)) {
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $payload = $request->all();
        $data = $payload['data'] ?? $payload;

        $reference = $data['tx_ref'] ?? $data['txRef'] ?? null;
        $status = strtolower((string)($data['status'] ?? ''));
        $txId = $data['id'] ?? null; // Flutterwave transaction ID

        if (!$reference) {
            return response()->json(['message' => 'No reference'], 400);
        }

        // Verify with Flutterwave for extra safety
        $secret = config('services.flutterwave.secret_key');
        if (!$secret) {
            Log::warning('Flutterwave secret key is not set');
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        if (is_string($txId)) {
            $txId = trim($txId);
        }

        $verify = null;
        try {
            if (!empty($txId)) {
                $verify = Http::withToken($secret)
                    ->acceptJson()
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->retry(3, 300)
                    ->get('https://api.flutterwave.com/v3/transactions/' . urlencode((string)$txId) . '/verify');
            } else {
                $verify = Http::withToken($secret)
                    ->acceptJson()
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->retry(3, 300)
                    ->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', [
                        'tx_ref' => $reference,
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Flutterwave verify threw exception', ['reference' => $reference, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Verification exception'], 400);
        }

        if (!$verify->ok() || ($verify->json('status') !== 'success')) {
            Log::warning('Flutterwave verify call failed', ['reference' => $reference, 'body' => $verify->json()]);
            return response()->json(['message' => 'Verification failed'], 400);
        }

        $vd = $verify->json('data');
        if (!$vd || strtolower((string)($vd['status'] ?? '')) !== 'successful') {
            $flwStatus = strtolower((string)($vd['status'] ?? ''));
            Log::info('Flutterwave verify not successful', ['reference' => $reference, 'status' => $vd['status'] ?? null]);

            // Notify member on explicit failure/cancellation
            if (in_array($flwStatus, ['failed', 'cancelled', 'canceled', 'error'], true)) {
                try {
                    $meta = $vd['meta'] ?? ($data['meta'] ?? []);
                    if (is_string($meta)) { $decoded = json_decode($meta, true); if (json_last_error() === JSON_ERROR_NONE) { $meta = $decoded; } }
                    if (is_object($meta)) { $meta = (array) $meta; }
                    $user = null;
                    $contrib = \App\Models\Contribution::where('reference', $reference)->first();
                    if ($contrib) { $user = \App\Models\User::find($contrib->user_id); }
                    if (!$user && isset($meta['user_id']) && is_numeric($meta['user_id'])) {
                        $user = \App\Models\User::find((int)$meta['user_id']);
                    }
                    if ($user) {
                        $reason = $vd['processor_response'] ?? ($vd['status'] ?? 'Payment failed');
                        if (!empty($user->email)) {
                            Mail::to($user->email)->send(new PaymentStatusMail(
                                status: 'failed',
                                title: 'Payment Failed',
                                message: 'Your payment attempt was not successful. ' . $reason,
                                amount: (float) ($vd['charged_amount'] ?? $vd['amount'] ?? 0),
                                reference: $reference,
                                channel: $vd['payment_type'] ?? null,
                                route: $contrib ? '/pay' : '/wallet',
                                meta: ['provider' => 'flutterwave']
                            ));
                        }
                        $push = app(\App\Services\PushService::class);
                        $token = $user->fcm_token ?: ($user->device_token ?? null);
                        $push->send($token, 'Payment Failed', 'Your payment attempt was not successful. ' . ($reason ?? ''), [
                            'type' => 'payment_failed',
                            'amount' => (float) ($vd['charged_amount'] ?? $vd['amount'] ?? 0),
                            'reference' => (string) $reference,
                            'route' => $contrib ? '/pay' : '/wallet',
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to send Flutterwave failure notification', ['reference' => $reference, 'error' => $e->getMessage()]);
                }
            }
            return response()->json(['message' => 'Not successful'], 200);
        }

        $amountNgn = (float) ($vd['amount'] ?? $vd['charged_amount'] ?? 0);
        $currency = $vd['currency'] ?? 'NGN';

        // 1) Loan Repayment path: our loan repayment init uses reference stored in qard_hasan_repayments.reference
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < (float) $loanRep->amount) {
                Log::warning('Flutterwave webhook: amount/currency mismatch for loan repayment', [
                    'reference' => $reference,
                    'paid_amount' => $amountNgn,
                    'expected' => (float) $loanRep->amount,
                    'currency' => $currency,
                ]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

            if ($loanRep->status === 'success') {
                return response()->json(['status' => 'ok']);
            }

            DB::transaction(function () use ($loanRep) {
                $loan = QardHasan::lockForUpdate()->find($loanRep->qard_hasan_id);
                if ($loan) {
                    $loanRep->status = 'success';
                    $loanRep->paid_at = now();
                    $loanRep->save();

                    $loan->paid_amount = (float) $loan->paid_amount + (float) $loanRep->amount;
                    if ($loan->paid_amount >= $loan->principal_amount) {
                        $loan->status = 'completed';
                    }
                    $loan->save();
                } else {
                    $loanRep->status = 'success';
                    $loanRep->paid_at = now();
                    $loanRep->save();
                    Log::warning('Loan not found when finalizing loan repayment from Flutterwave', [
                        'repayment_id' => $loanRep->id,
                        'qard_hasan_id' => $loanRep->qard_hasan_id,
                    ]);
                }
            });

            // Send repayment receipt to user (best-effort)
            try {
                $loanRep->refresh();
                $loan = QardHasan::with('user')->find($loanRep->qard_hasan_id);
                if ($loan && $loan->user && !empty($loan->user->email)) {
                    Mail::to($loan->user->email)->send(new RepaymentReceiptUser($loan, $loanRep));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send repayment receipt email (flutterwave path)', [
                    'reference' => $reference,
                    'repayment_id' => $loanRep->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Best-effort SMS + Push notification to member
            try {
                $loan = QardHasan::with('user')->find($loanRep->qard_hasan_id);
                if ($loan && $loan->user) {
                    $remaining = max(0, (float) $loan->principal_amount - (float) $loan->paid_amount);
                    $sms = app(\App\Services\SmsService::class);
                    $push = app(\App\Services\PushService::class);
                    $msg = 'Loan repayment received: ₦'.number_format((float)$loanRep->amount, 2).' for '.($loan->qard_id_string).'. Remaining: ₦'.number_format($remaining, 2).'. Ref: '.($loanRep->reference);
                    $sms->send($loan->user->phone ?? null, $msg);
                    $token = $loan->user->fcm_token ?: ($loan->user->device_token ?? null);
                    $push->send($token, 'Repayment Received', $msg, [
                        'type' => 'loan_repayment',
                        'loan_id' => $loan->id,
                        'qard_id_string' => $loan->qard_id_string,
                        'amount' => (float) $loanRep->amount,
                        'reference' => (string) $loanRep->reference,
                    ]);
                }
            } catch (\Throwable $e) {
                // ignore notification errors
            }

            return response()->json(['status' => 'success']);
        }

        // 2) Scheme Contributions path
        $contributions = Contribution::where('reference', $reference)
            ->where('status', 'pending')
            ->get();

        if ($contributions->isNotEmpty()) {
            $expectedTotal = (float) $contributions->sum('amount');
            if ($currency !== 'NGN' || ($amountNgn + 0.0001) < $expectedTotal) {
                Log::warning('Flutterwave amount/currency mismatch', [
                    'reference' => $reference,
                    'expected' => $expectedTotal,
                    'paid' => $amountNgn,
                    'currency' => $currency,
                ]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

            $user = User::find($contributions->first()->user_id);

            foreach ($contributions as $contribution) {
                $contribution->status = 'success';
                $contribution->save();
            }

            // Notify user (non-blocking) + best-effort Push
            try {
                if ($user) {
                    $user->notify(new PaymentNotification(
                        title: 'Payment Successful',
                        message: 'Your payment has been received and allocated to your schemes.',
                        amount: $expectedTotal,
                        reference: $reference,
                        source: 'scheme_payment'
                    ));

                    // Email receipt (best-effort)
                    try {
                        if (!empty($user->email)) {
                            Mail::to($user->email)->send(new PaymentStatusMail(
                                status: 'success',
                                title: 'Payment Successful',
                                message: 'Your payment has been received and allocated to your schemes.',
                                amount: $expectedTotal,
                                reference: $reference,
                                channel: $vd['payment_type'] ?? null,
                                route: '/passbook',
                                meta: ['provider' => 'flutterwave']
                            ));
                        }
                    } catch (\Throwable $e) {}

                    // Fire push notification to device
                    $push = app(\App\Services\PushService::class);
                    $token = $user->fcm_token ?: ($user->device_token ?? null);
                    $push->send($token, 'Payment Successful', 'Your payment has been received and allocated to your schemes.', [
                        'type' => 'scheme_payment',
                        'amount' => (float) $expectedTotal,
                        'reference' => (string) $reference,
                        'route' => '/passbook',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send scheme payment notification (flutterwave)', [
                    'reference' => $reference,
                    'user_id' => optional($user)->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Flutterwave payment processed for schemes', ['reference' => $reference, 'user_id' => optional($user)->id]);
            return response()->json(['status' => 'success']);
        }

        // 3) Wallet Top-up path (no pending contributions and not a loan repayment)
        $meta = $vd['meta'] ?? ($data['meta'] ?? []);
        $userId = $meta['user_id'] ?? null;
        $topupUser = $userId ? User::find($userId) : null;

        if (!$topupUser) {
            Log::warning('Flutterwave wallet top-up: user not found', [
                'reference' => $reference,
                'user_id' => $userId,
            ]);
            // Acknowledge to stop retries; manual reconciliation can fix it.
            return response()->json(['status' => 'ignored']);
        }

        if ($currency !== 'NGN' || $amountNgn <= 0) {
            Log::warning('Flutterwave wallet top-up invalid currency/amount', [
                'reference' => $reference,
                'amount' => $amountNgn,
                'currency' => $currency,
            ]);
            return response()->json(['status' => 'ignored']);
        }

        // Idempotency check
        if (WalletTransaction::where('reference', $reference)->exists()) {
            return response()->json(['status' => 'ok']);
        }

        DB::transaction(function () use ($topupUser, $amountNgn, $reference, $vd) {
            $topupUser->increment('balance', $amountNgn);

            WalletTransaction::create([
                'user_id' => $topupUser->id,
                'type' => 'credit',
                'amount' => $amountNgn,
                'reference' => $reference,
                'source' => 'flutterwave_charge',
                'meta' => [
                    'channel' => $vd['payment_type'] ?? null,
                    'flw_ref' => $vd['flw_ref'] ?? null,
                    'processor' => 'flutterwave',
                ],
            ]);
        });

        Log::info('Flutterwave wallet top-up processed', [
            'reference' => $reference,
            'user_id' => $topupUser->id,
        ]);

        // Notify user (non-blocking)
        try {
            $topupUser->notify(new PaymentNotification(
                title: 'Wallet Top-up Successful',
                message: 'Your wallet has been credited successfully.',
                amount: $amountNgn,
                reference: $reference,
                source: 'wallet_topup'
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send wallet top-up notification (flutterwave)', [
                'reference' => $reference,
                'user_id' => $topupUser->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Best-effort SMS notification
        try {
            $fresh = $topupUser->fresh();
            $newBal = (float) ($fresh->balance ?? 0);
            $sms = app(\App\Services\SmsService::class);
            $msg = 'Wallet top-up: ₦'.number_format($amountNgn, 2).". New bal: ₦".number_format($newBal, 2).'. Ref: '.$reference;
            $sms->send($fresh->phone ?? null, $msg);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        return response()->json(['status' => 'success']);
    }
}
