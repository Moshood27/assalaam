<?php

namespace App\Http\Controllers\Api;

use App\Notifications\PaymentNotification;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserVirtualAccount;
use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ProjectInvestment;
use App\Models\SadaqahProject;
use App\Models\SadaqahContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RepaymentReceiptUser;
use App\Mail\PaymentStatusMail;
use App\Services\SmsService;
use App\Services\TakafulService;
use App\Services\AdministrativeChargeService;
use App\Services\OpayService;
use App\Services\MonnifyService;
use App\Services\PaystackService;

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
                if ($customerCode) {
                    $user = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
                }
                if (!$user && isset($meta['user_id'])) { $uid = is_numeric($meta['user_id']) ? (int)$meta['user_id'] : null; if ($uid) { $user = \App\Models\User::find($uid); } }
            }
            if ($user) {
                // Capture to Sentry to notify admin immediately
                if (app()->bound('sentry')) {
                    app('sentry')->captureMessage('Payment Failure: Paystack ' . $reason, \Sentry\Severity::error());
                }

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
                    $user->notifyMember('Payment Failed', 'Your payment attempt was not successful. ' . $reason, [
                        'type' => 'payment_failed',
                        'amount' => $amountNgn,
                        'reference' => (string) ($reference ?? ''),
                        'route' => $reference && \App\Models\Contribution::where('reference', $reference)->exists() ? '/pay' : '/wallet',
                    ], ['push', 'database']);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send Paystack failure notification', ['reference' => $reference, 'error' => $e->getMessage()]);
                }
            }
            return response()->json(['status' => 'ok']);
        }

        if ($event === 'customeridentification.failed') {
            $customerCode = $data['customer_code'] ?? null;
            if ($customerCode) {
                // Find the user linked to this customer code
                $user = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();

                if ($user) {
                    $reason = $data['message'] ?? ($data['reason'] ?? 'Name mismatch or invalid BVN');
                    // 1. Log the failure reason for admin
                    Log::error("Paystack KYC Failed for Member ID: {$user->id} ({$user->email}). Reason: {$reason}");

                    // 2. Notify the user so they stop waiting
                    $user->notifyMember(
                        'Action Required: KYC Failed',
                        "Your bank rejected the BVN identification. Reason: {$reason}. Please ensure your profile name matches the name on your BVN exactly.",
                        ['type' => 'kyc_failed', 'route' => '/profile']
                    );
                }
            }
            return response()->json(['status' => 'success']);
        }

        if ($event === 'customeridentification.success') {
            $customerCode = $data['customer_code'] ?? null;
            if ($customerCode) {
                $user = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
                if ($user && empty($user->virtualAccount?->dva_account_number)) {
                    $paystack = app(PaystackService::class);
                    $paystack->assignDva($user, $customerCode);
                    Log::info("Paystack KYC Success: Auto-assigned DVA for Member ID: {$user->id}");
                }
            }
            return response()->json(['status' => 'success']);
        }

        if ($event === 'dedicatedaccount.assign.success') {
            $acc = $data['dedicated_account'] ?? null;
            $customerCode = $acc['customer']['customer_code'] ?? ($data['customer']['customer_code'] ?? null);
            if ($customerCode && $acc) {
                $user = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
                if ($user) {
                    $user->virtualAccount()->updateOrCreate([], [
                        'dva_account_number' => $acc['account_number'],
                        'dva_account_name'   => $acc['account_name'],
                        'dva_bank_name'      => $acc['bank']['name'] ?? ($acc['provider']['name'] ?? 'Bank'),
                    ]);

                    $user->notifyMember(
                        'Virtual Account Ready',
                        "Your Paystack Virtual Account has been successfully assigned and is ready for use.",
                        ['type' => 'dva_assigned', 'route' => '/wallet']
                    );
                }
            }
            return response()->json(['status' => 'success']);
        }

        if ($event === 'dedicatedaccount.assign.failed') {
            $customerCode = $data['customer']['customer_code'] ?? null;
            if ($customerCode) {
                $user = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
                if ($user) {
                    $reason = $data['message'] ?? 'We encountered an issue assigning your Virtual Account.';
                    Log::warning("Paystack DVA Assignment Failed for Member ID: {$user->id}. Reason: {$reason}");
                    $user->notifyMember(
                        'Virtual Account Failed',
                        "{$reason} Please try again later or contact support.",
                        ['type' => 'dva_failed', 'route' => '/wallet']
                    );
                }
            }
            return response()->json(['status' => 'success']);
        }

        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;
            if (! $reference) {
                return response()->json(['message' => 'No reference'], 400);
            }

            // Verify transaction with Paystack for extra safety
            $paystack = app(PaystackService::class);
            $verifyResult = $paystack->verifyTransaction($reference);

            if (! $verifyResult['success']) {
                Log::warning('Paystack verify call failed', ['reference' => $reference, 'message' => $verifyResult['message']]);
                return response()->json(['message' => 'Verification failed'], 400);
            }

            $vd = $verifyResult['data'];
            if (! $vd || ($vd['status'] ?? null) !== 'success') {
                Log::info('Paystack verify not successful', ['reference' => $reference, 'status' => $vd['status'] ?? null]);
                return response()->json(['message' => 'Not successful'], 200);
            }

            // Sum expected amount from pending contributions
            $contributions = Contribution::where('reference', $reference)
                ->where('status', 'pending')
                ->get();

            if ($contributions->isEmpty()) {
                // Check if this is a Sadaqah Contribution
                $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
                if ($sadaqahContrib) {
                    $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);
                    $paidCurrency = $vd['currency'] ?? 'NGN';
                    if ($paidCurrency !== 'NGN' || ($amountNgn + 0.005) < (float) $sadaqahContrib->amount) {
                        Log::warning('Paystack webhook: amount/currency mismatch for sadaqah', [
                            'reference' => $reference,
                            'paid_amount' => $amountNgn,
                            'expected' => (float) $sadaqahContrib->amount,
                            'currency' => $paidCurrency,
                        ]);
                        return response()->json(['message' => 'Amount mismatch'], 400);
                    }

                    if ($sadaqahContrib->status === 'success') {
                        return response()->json(['status' => 'ok']);
                    }

                    DB::transaction(function () use ($sadaqahContrib) {
                        $sadaqahContrib->status = 'success';
                        $sadaqahContrib->save();

                        $project = SadaqahProject::lockForUpdate()->find($sadaqahContrib->sadaqah_project_id);
                        if ($project) {
                            $project->raised_amount = (float) $project->raised_amount + (float) $sadaqahContrib->amount;
                            $project->save();
                        }
                    });

                    // Notify user via unified method (triggers real-time update)
                    try {
                        $user = User::find($sadaqahContrib->user_id);
                        if ($user) {
                            $project = SadaqahProject::find($sadaqahContrib->sadaqah_project_id);
                            $user->notifyMember(
                                'Sadaqah Contribution Successful',
                                "Your contribution of â‚¦" . number_format($sadaqahContrib->amount, 2) . " to " . ($project->name ?? 'Project') . " was successful. Jazakallah Khair.",
                                [
                                    'type' => 'sadaqah_contribution',
                                    'amount' => (float) $sadaqahContrib->amount,
                                    'reference' => $sadaqahContrib->reference,
                                    'route' => '/sadaqah',
                                ]
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send Sadaqah webhook notification (Paystack)', ['error' => $e->getMessage()]);
                    }

                    return response()->json(['status' => 'success']);
                }

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

                    // Unified real-time/push/email/sms notification
                    try {
                        $loan = QardHasan::with('user')->find($loanRep->qard_hasan_id);
                        if ($loan && $loan->user) {
                            $remaining = max(0, (float) $loan->principal_amount - (float) $loan->paid_amount);
                            $msg = 'Loan repayment received: â‚¦'.number_format((float)$loanRep->amount, 2).' for '.($loan->qard_id_string).'. Remaining: â‚¦'.number_format($remaining, 2).'.';
                            $loan->user->notifyMember(
                                'Repayment Received',
                                $msg,
                                [
                                    'type' => 'loan_repayment',
                                    'loan_id' => $loan->id,
                                    'qard_id_string' => $loan->qard_id_string,
                                    'remaining_balance' => $remaining,
                                    'reference' => (string) $loanRep->reference,
                                    'route' => '/loan/' . $loan->id,
                                ]
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send loan repayment notification (paystack path)', ['error' => $e->getMessage()]);
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
                    $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
                }
                if (! $topupUser && $receiverAccount) {
                    $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('dva_account_number', $receiverAccount))->first();
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

                $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
                $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

                if ($currency !== 'NGN' || $amountNgn <= 0) {
                    Log::warning('Paystack webhook: invalid currency/amount for wallet topup', [
                        'reference' => $reference,
                        'amount_ngn' => $amountNgn,
                        'currency' => $currency,
                    ]);
                    return response()->json(['status' => 'ignored']);
                }

                // Idempotency: if we've already recorded this reference or Paystack ID as a wallet transaction, skip
                $paystackId = $vd['id'] ?? null;
                $alreadyProcessed = $paystackId ? WalletTransaction::where('meta->paystack_id', $paystackId)->exists() : false;

                if ($alreadyProcessed || WalletTransaction::where('reference', $reference)->exists()) {
                    return response()->json(['status' => 'ok']);
                }

                DB::transaction(function () use ($topupUser, $amountNgn, $netAmount, $maintenanceCharge, $reference, $vdChannel, $vd, $customerCode, $metadata, $paystackId) {
                    // Persist Paystack customer code and authorization code for future lookups/charges
                    $vaData = [];
                    if (empty($topupUser->paystack_customer_code) && !empty($customerCode)) {
                        $vaData['paystack_customer_code'] = $customerCode;
                    }
                    $authCode = $vd['authorization']['authorization_code'] ?? null;
                    if (empty($topupUser->paystack_authorization_code) && !empty($authCode)) {
                        $vaData['paystack_authorization_code'] = $authCode;
                    }
                    if (!empty($vaData)) {
                        $topupUser->virtualAccount()->updateOrCreate([], $vaData);
                    }

                    // Credit wallet
                    $topupUser->increment('balance', $netAmount);

                    // Detect autosave via metadata
                    $isAutosave = is_array($metadata) && (($metadata['type'] ?? null) === 'autosave');
                    $source = $vdChannel === 'bank_transfer' ? 'paystack_dva' : ($isAutosave ? 'paystack_autosave' : 'paystack_charge');

                    // Record wallet credit transaction
                    WalletTransaction::create([
                        'user_id' => $topupUser->id,
                        'type' => 'credit',
                        'amount' => $netAmount,
                        'reference' => $reference,
                        'source' => $source,
                        'meta' => [
                            'paystack_id' => $paystackId,
                            'channel' => $vdChannel,
                            'customer_code' => $vd['customer']['customer_code'] ?? null,
                            'receiver_account' => $vd['authorization']['receiver_bank_account_number'] ?? ($vd['authorization']['account_number'] ?? null),
                            'maintenance_charge' => $maintenanceCharge,
                            'gross_amount' => $amountNgn,
                            'metadata' => $metadata,
                        ],
                    ]);
                });

                Log::info('Paystack wallet top-up processed', [
                    'reference' => $reference,
                    'user_id' => $topupUser->id,
                    'channel' => $vdChannel,
                ]);

                // Notify user via unified method (triggers real-time, push, mail, sms as per prefs)
                $topupUser->notifyMember(
                    'Wallet Top-up Successful',
                    "Your wallet has been credited with â‚¦" . number_format($netAmount, 2) . " after a maintenance charge of â‚¦" . number_format($maintenanceCharge, 2) . ".",
                    [
                        'type' => 'wallet_topup',
                        'amount' => (float) $netAmount,
                        'reference' => (string) $reference,
                        'route' => '/wallet',
                    ]
                );

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

            // Persist Paystack customer/authorization codes on user for future autosave charges
            try {
                if ($user) {
                    $vaData = [];
                    $custCode = $vd['customer']['customer_code'] ?? null;
                    if (empty($user->paystack_customer_code) && !empty($custCode)) {
                        $vaData['paystack_customer_code'] = $custCode;
                    }
                    $authCode = $vd['authorization']['authorization_code'] ?? null;
                    if (empty($user->paystack_authorization_code) && !empty($authCode)) {
                        $vaData['paystack_authorization_code'] = $authCode;
                    }
                    if (!empty($vaData)) {
                        $user->virtualAccount()->updateOrCreate([], $vaData);
                    }
                }
            } catch (\Throwable $e) {
                // ignore persistence error; not critical for payment finalization
            }

            foreach ($contributions as $contribution) {
                $contribution->status = 'success';
                $contribution->save();

                // If this is Zakat or Zakat Al-Fitr, record it in the Charity Ledger and move to Fund
                $schemeName = $contribution->scheme?->name;
                if ($schemeName && in_array($schemeName, ['Zakat', 'Zakat Al-Fitr'])) {
                    \App\Models\CharityEntry::create([
                        'user_id' => $contribution->user_id,
                        'source' => $schemeName,
                        'amount' => $contribution->amount,
                        'note' => "Payment for {$schemeName} via Paystack (Ref: {$reference})",
                    ]);

                    // Move to Zakat Fund (SadaqahProject)
                    $zakatProject = SadaqahProject::firstOrCreate(
                        ['name' => 'General Zakat Fund'],
                        ['description' => 'Automated Zakat Fund', 'active' => true]
                    );

                    SadaqahContribution::create([
                        'user_id' => $contribution->user_id,
                        'sadaqah_project_id' => $zakatProject->id,
                        'amount' => $contribution->amount,
                        'status' => 'success',
                        'reference' => 'ZAKAT_FUND_MOVE_EXT_' . now()->format('YmdHis'),
                    ]);

                    $zakatProject->increment('raised_amount', $contribution->amount);

                    if ($schemeName === 'Zakat') {
                        $user->update([
                            'zakat_last_paid_at' => now(),
                            'zakat_nisab_crossed_at' => now(), // Start next Hawl cycle
                        ]);
                    }
                }
            }

            // Notify user via unified method (triggers real-time update)
            // Note: Individual contributions also trigger their own notifications via model observers.
            // This global notification covers the total payment.
            if ($user) {
                $user->notifyMember(
                    'Payment Successful',
                    'Your payment of â‚¦' . number_format($expectedTotal, 2) . ' has been received and allocated to your schemes.',
                    [
                        'type' => 'scheme_payment',
                        'amount' => (float) $expectedTotal,
                        'reference' => (string) $reference,
                        'route' => '/passbook',
                    ]
                );
            }

            // Do not credit wallet here. Contributions were paid directly to schemes via this reference.
            // Wallet top-ups are handled in the branch above when no pending contributions exist.

            Log::info('Paystack payment processed', ['reference' => $reference, 'user_id' => optional($user)->id]);
        }

        // Handle Transfer Webhooks (for Expenses and Payouts)
        if (in_array($event, ['transfer.success', 'transfer.failed', 'transfer.reversed'])) {
            $transfer = $data;
            $reference = $transfer['reference'] ?? null;
            $transferCode = $transfer['transfer_code'] ?? null;

            if ($reference) {
                $expense = \App\Models\ExpenseEntry::where('payout_reference', $reference)->first();

                if ($expense) {
                    if ($event === 'transfer.success') {
                        $expense->update(['status' => 'processed', 'processed_at' => now()]);
                        Log::info("Expense payout successful via webhook", ['expense_id' => $expense->id, 'reference' => $reference]);

                        // Notify creator
                        if ($expense->creator) {
                            $expense->creator->notify(new \App\Notifications\GeneralNotification(
                                title: 'Expense Payout Successful',
                                message: "The payout for '{$expense->title}' (â‚¦" . number_format($expense->amount, 2) . ") has been successfully processed to the bank.",
                                data: ['type' => 'expense_payout_success', 'expense_id' => $expense->id]
                            ));
                        }
                    } elseif ($event === 'transfer.failed' || $event === 'transfer.reversed') {
                        $reason = $transfer['reason'] ?? ($transfer['gateway_response'] ?? 'Unknown error');
                        $expense->update(['status' => 'approved', 'rejection_reason' => "Transfer failed/reversed: " . $reason]);
                        Log::warning("Expense payout failed/reversed via webhook", ['expense_id' => $expense->id, 'reference' => $reference, 'event' => $event]);

                        // Notify creator and Treasurer
                        $message = "The payout for '{$expense->title}' (â‚¦" . number_format($expense->amount, 2) . ") failed: {$reason}. It has been reset to 'approved' for retry.";
                        $notification = new \App\Notifications\GeneralNotification(
                            title: 'Expense Payout Failed',
                            message: $message,
                            data: ['type' => 'expense_payout_failed', 'expense_id' => $expense->id]
                        );

                        if ($expense->creator) { $expense->creator->notify($notification); }

                        $treasurers = \App\Models\User::whereHas('roles', function($q) {
                            $q->whereIn('name', ['Treasurer', 'super_admin']);
                        })->get();
                        foreach ($treasurers as $treasurer) {
                            $treasurer->notify($notification);
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function handleFlutterwave(Request $request)
    {
        // Verify webhook signature using FLW_SECRET_HASH
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.secret_hash');

        if (!$secretHash || !$signature || !hash_equals((string)$secretHash, (string)$signature)) {
            Log::warning('Flutterwave webhook signature verification failed', [
                'has_config_hash' => !empty($secretHash),
                'has_header_hash' => !empty($signature),
                'ip' => $request->ip(),
            ]);
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

                        // Capture to Sentry to notify admin immediately
                        if (app()->bound('sentry')) {
                            app('sentry')->captureMessage('Payment Failure: Flutterwave ' . $reason, \Sentry\Severity::error());
                        }

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
                        $user->notifyMember('Payment Failed', 'Your payment attempt was not successful. ' . ($reason ?? ''), [
                            'type' => 'payment_failed',
                            'amount' => (float) ($vd['charged_amount'] ?? $vd['amount'] ?? 0),
                            'reference' => (string) $reference,
                            'route' => $contrib ? '/pay' : '/wallet',
                        ], ['push', 'database']);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to send Flutterwave failure notification', ['reference' => $reference, 'error' => $e->getMessage()]);
                }
            }
            return response()->json(['message' => 'Not successful'], 200);
        }

        $amountNgn = (float) ($vd['amount'] ?? $vd['charged_amount'] ?? 0);
        $currency = $vd['currency'] ?? 'NGN';

        $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
        $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

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

            // Unified real-time/push/email/sms notification
            try {
                $loan = QardHasan::with('user')->find($loanRep->qard_hasan_id);
                if ($loan && $loan->user) {
                    $remaining = max(0, (float) $loan->principal_amount - (float) $loan->paid_amount);
                    $msg = 'Loan repayment received: â‚¦'.number_format((float)$loanRep->amount, 2).' for '.($loan->qard_id_string).'. Remaining: â‚¦'.number_format($remaining, 2).'.';
                    $loan->user->notifyMember(
                        'Repayment Received',
                        $msg,
                        [
                            'type' => 'loan_repayment',
                            'loan_id' => $loan->id,
                            'qard_id_string' => $loan->qard_id_string,
                            'remaining_balance' => $remaining,
                            'reference' => (string) $loanRep->reference,
                            'route' => '/loan/' . $loan->id,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send loan repayment notification (flutterwave path)', ['error' => $e->getMessage()]);
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

                // If this is Zakat or Zakat Al-Fitr, record it in the Charity Ledger and move to Fund
                $schemeName = $contribution->scheme?->name;
                if ($schemeName && in_array($schemeName, ['Zakat', 'Zakat Al-Fitr'])) {
                    \App\Models\CharityEntry::create([
                        'user_id' => $contribution->user_id,
                        'source' => $schemeName,
                        'amount' => $contribution->amount,
                        'note' => "Payment for {$schemeName} via Flutterwave (Ref: {$reference})",
                    ]);

                    // Move to Zakat Fund (SadaqahProject)
                    $zakatProject = SadaqahProject::firstOrCreate(
                        ['name' => 'General Zakat Fund'],
                        ['description' => 'Automated Zakat Fund', 'active' => true]
                    );

                    SadaqahContribution::create([
                        'user_id' => $contribution->user_id,
                        'sadaqah_project_id' => $zakatProject->id,
                        'amount' => $contribution->amount,
                        'status' => 'success',
                        'reference' => 'ZAKAT_FUND_MOVE_EXT_' . now()->format('YmdHis'),
                    ]);

                    $zakatProject->increment('raised_amount', $contribution->amount);

                    if ($schemeName === 'Zakat') {
                        $user->update([
                            'zakat_last_paid_at' => now(),
                            'zakat_nisab_crossed_at' => now(), // Start next Hawl cycle
                        ]);
                    }
                }
            }

            // Unified notification (triggers real-time, push, mail, etc)
            if ($user) {
                $user->notifyMember(
                    'Payment Successful',
                    'Your payment of â‚¦' . number_format($expectedTotal, 2) . ' has been received and allocated to your schemes.',
                    [
                        'type' => 'scheme_payment',
                        'amount' => (float) $expectedTotal,
                        'reference' => (string) $reference,
                        'route' => '/passbook',
                    ]
                );
            }

            Log::info('Flutterwave payment processed for schemes', ['reference' => $reference, 'user_id' => optional($user)->id]);
            return response()->json(['status' => 'success']);
        }

        // Check if this is a Sadaqah Contribution
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < (float) $sadaqahContrib->amount) {
                Log::warning('Flutterwave webhook: amount/currency mismatch for sadaqah', [
                    'reference' => $reference,
                    'paid_amount' => $amountNgn,
                    'expected' => (float) $sadaqahContrib->amount,
                    'currency' => $currency,
                ]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

            if ($sadaqahContrib->status === 'success') {
                return response()->json(['status' => 'ok']);
            }

            DB::transaction(function () use ($sadaqahContrib) {
                $sadaqahContrib->status = 'success';
                $sadaqahContrib->save();

                $project = SadaqahProject::lockForUpdate()->find($sadaqahContrib->sadaqah_project_id);
                if ($project) {
                    $project->raised_amount = (float) $project->raised_amount + (float) $sadaqahContrib->amount;
                    $project->save();
                }
            });

            // Notify user
            // Notify user via unified method (triggers real-time update)
            try {
                $user = User::find($sadaqahContrib->user_id);
                if ($user) {
                    $project = SadaqahProject::find($sadaqahContrib->sadaqah_project_id);
                    $user->notifyMember(
                        'Sadaqah Contribution Successful',
                        "Your contribution of â‚¦" . number_format($sadaqahContrib->amount, 2) . " to " . ($project->name ?? 'Project') . " was successful. Jazakallah Khair.",
                        [
                            'type' => 'sadaqah_contribution',
                            'amount' => (float) $sadaqahContrib->amount,
                            'reference' => $sadaqahContrib->reference,
                            'route' => '/sadaqah',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send Sadaqah webhook notification (Flutterwave)', ['error' => $e->getMessage()]);
            }

            return response()->json(['status' => 'success']);
        }

        // 3) Wallet Top-up path (no pending contributions and not a loan repayment)
        $meta = $vd['meta'] ?? ($data['meta'] ?? []);
        if (is_string($meta)) { $decoded = json_decode($meta, true); if (json_last_error() === JSON_ERROR_NONE) { $meta = $decoded; } }

        $userId = $meta['user_id'] ?? null;
        if (!$userId && str_starts_with((string)$reference, 'DVA_')) {
            $parts = explode('_', (string)$reference);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $userId = (int)$parts[2];
            }
        }
        if (is_string($userId) && ctype_digit($userId)) { $userId = (int) $userId; }

        $topupUser = $userId ? User::find($userId) : null;

        // Identification by DVA account number for spontaneous bank transfers
        if (!$topupUser && (($vd['payment_type'] ?? null) === 'bank_transfer')) {
            $accountNumber = $vd['bank_transfer_details']['account_number'] ?? ($meta['virtual_account_number'] ?? null);
            if ($accountNumber) {
                $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('flw_dva_data->account_number', $accountNumber))->first();
                if (!$topupUser) {
                    $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('dva_account_number', $accountNumber))->first();
                }
            }
            if (!$topupUser && !empty($vd['customer']['email'])) {
                $topupUser = User::where('email', $vd['customer']['email'])->first();
            }
        }

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

        $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
        $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

        // Idempotency check
        $isDva = ($vd['payment_type'] ?? null) === 'bank_transfer';
        $dbReference = $isDva ? ($vd['flw_ref'] ?? $reference) : $reference;

        if (WalletTransaction::where('reference', $dbReference)->exists()) {
            return response()->json(['status' => 'ok']);
        }

        DB::transaction(function () use ($topupUser, $amountNgn, $netAmount, $maintenanceCharge, $dbReference, $vd, $isDva) {
            $topupUser->increment('balance', $netAmount);

            $source = $isDva ? 'flutterwave_dva' : 'flutterwave_charge';

            WalletTransaction::create([
                'user_id' => $topupUser->id,
                'type' => 'credit',
                'amount' => $netAmount,
                'reference' => $dbReference,
                'source' => $source,
                'meta' => [
                    'channel' => $vd['payment_type'] ?? null,
                    'flw_ref' => $vd['flw_ref'] ?? null,
                    'processor' => 'flutterwave',
                    'maintenance_charge' => $maintenanceCharge,
                    'gross_amount' => $amountNgn,
                ],
            ]);
        });

        Log::info('Flutterwave wallet top-up processed', [
            'reference' => $reference,
            'user_id' => $topupUser->id,
        ]);

        // Notify user via unified method (triggers real-time, push, mail, sms as per prefs)
        $topupUser->notifyMember(
            'Wallet Top-up Successful',
            "Your wallet has been credited with â‚¦" . number_format($netAmount, 2) . " after a maintenance charge of â‚¦" . number_format($maintenanceCharge, 2) . ".",
            [
                'type' => 'wallet_topup',
                'amount' => (float) $netAmount,
                'reference' => (string) $reference,
                'route' => '/wallet',
            ]
        );

        return response()->json(['status' => 'success']);
    }

    /**
     * Calculate system maintenance charge for wallet top-ups.
     * Uses dynamic settings if available, otherwise falls back to config.
     *
     * @param float $amount
     * @return float
     */
    public function handleMonnify(Request $request)
    {
        $signature = $request->header('x-monnify-signature');
        $secret = config('services.monnify.secret_key');

        if (!$signature || ($signature !== hash_hmac('sha512', $request->getContent(), (string)$secret))) {
            Log::warning('Monnify webhook signature verification failed', [
                'has_config_secret' => !empty($secret),
                'has_header_signature' => !empty($signature),
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $payload = $request->all();
        $eventType = $payload['eventType'] ?? null;
        $data = $payload['eventData'] ?? [];

        if ($eventType !== 'SUCCESSFUL_TRANSACTION') {
            return response()->json(['status' => 'ignored']);
        }

        $reference = $data['paymentReference'] ?? null;
        if (!$reference) {
            return response()->json(['message' => 'No reference'], 400);
        }

        // Verify with Monnify for extra safety
        $service = app(MonnifyService::class);
        $verifiedData = $service->verifyTransaction($reference);

        if (!$verifiedData || ($verifiedData['paymentStatus'] ?? '') !== 'PAID') {
            Log::warning('Monnify verify call failed or not PAID', ['reference' => $reference, 'body' => $verifiedData]);
            return response()->json(['message' => 'Verification failed'], 400);
        }

        $amountNgn = (float)($verifiedData['amountPaid'] ?? 0);
        $currency = $verifiedData['currencyCode'] ?? 'NGN';

        // 1) Contribution path
        $contributions = Contribution::where('reference', $reference)
            ->where('status', 'pending')
            ->get();

        if ($contributions->isNotEmpty()) {
            $expectedTotal = (float) $contributions->sum('amount');
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < $expectedTotal) {
                Log::warning('Monnify webhook: amount/currency mismatch for contributions', [
                    'reference' => $reference,
                    'paid_amount' => $amountNgn,
                    'expected' => $expectedTotal,
                    'currency' => $currency,
                ]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

            DB::transaction(function () use ($contributions) {
                foreach ($contributions as $contrib) {
                    $contrib->update(['status' => 'success']);
                }
            });

            // Notify user
            try {
                $user = User::find($contributions->first()->user_id);
                if ($user) {
                    $user->notifyMember(
                        'Payment Successful',
                        "Your payment of â‚¦" . number_format($amountNgn, 2) . " for " . $contributions->count() . " items was successful.",
                        [
                            'type' => 'payment_success',
                            'amount' => (float) $amountNgn,
                            'reference' => $reference,
                            'route' => '/pay',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send Monnify contribution notification', ['error' => $e->getMessage()]);
            }

            return response()->json(['status' => 'success']);
        }

        // 2) Sadaqah path
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < (float) $sadaqahContrib->amount) {
                return response()->json(['message' => 'Amount mismatch'], 400);
            }
            if ($sadaqahContrib->status === 'success') {
                return response()->json(['status' => 'ok']);
            }
            DB::transaction(function () use ($sadaqahContrib) {
                $sadaqahContrib->update(['status' => 'success']);
                $project = SadaqahProject::lockForUpdate()->find($sadaqahContrib->sadaqah_project_id);
                if ($project) {
                    $project->increment('raised_amount', (float) $sadaqahContrib->amount);
                }
            });
            return response()->json(['status' => 'success']);
        }

        // 3) Loan Repayment path
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < (float) $loanRep->amount) {
                return response()->json(['message' => 'Amount mismatch'], 400);
            }
            if ($loanRep->status === 'success') {
                return response()->json(['status' => 'ok']);
            }
            DB::transaction(function () use ($loanRep) {
                $loan = QardHasan::lockForUpdate()->find($loanRep->qard_hasan_id);
                if ($loan) {
                    $loanRep->update(['status' => 'success', 'paid_at' => now()]);
                    $loan->increment('paid_amount', (float) $loanRep->amount);
                    if ($loan->paid_amount >= $loan->principal_amount) {
                        $loan->update(['status' => 'completed']);
                    }
                }
            });
            return response()->json(['status' => 'success']);
        }

        // 4) Wallet Top-up path
        $meta = $verifiedData['metaData'] ?? [];
        if (is_string($meta)) { $decoded = json_decode($meta, true); if (json_last_error() === JSON_ERROR_NONE) { $meta = $decoded; } }

        $userId = $meta['user_id'] ?? null;
        $topupUser = $userId ? User::find($userId) : null;

        // DVA lookup
        if (!$topupUser) {
            $customerReference = $verifiedData['customer']['customerReference'] ?? null;
            if ($customerReference) {
                $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('monnify_customer_reference', $customerReference))->first();
            }
            if (!$topupUser && !empty($verifiedData['customer']['email'])) {
                $topupUser = User::where('email', $verifiedData['customer']['email'])->first();
            }
        }

        if (!$topupUser) {
            Log::warning('Monnify wallet top-up: user not found', ['reference' => $reference]);
            return response()->json(['status' => 'ignored']);
        }

        if ($currency !== 'NGN' || $amountNgn <= 0) {
            return response()->json(['status' => 'ignored']);
        }

        $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
        $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

        if (WalletTransaction::where('reference', $reference)->exists()) {
            return response()->json(['status' => 'ok']);
        }

        DB::transaction(function () use ($topupUser, $amountNgn, $netAmount, $maintenanceCharge, $reference, $verifiedData) {
            $topupUser->increment('balance', $netAmount);
            WalletTransaction::create([
                'user_id' => $topupUser->id,
                'type' => 'credit',
                'amount' => $netAmount,
                'reference' => $reference,
                'source' => 'monnify_topup',
                'meta' => [
                    'processor' => 'monnify',
                    'maintenance_charge' => $maintenanceCharge,
                    'gross_amount' => $amountNgn,
                    'payment_method' => $verifiedData['paymentMethod'] ?? null,
                ],
            ]);
        });

        $topupUser->notifyMember(
            'Wallet Top-up Successful',
            "Your wallet has been credited with â‚¦" . number_format($netAmount, 2) . " after a maintenance charge of â‚¦" . number_format($maintenanceCharge, 2) . ".",
            [
                'type' => 'wallet_topup',
                'amount' => (float) $netAmount,
                'reference' => $reference,
                'route' => '/wallet',
            ]
        );

        return response()->json(['status' => 'success']);
    }

    public function handleOpay(Request $request)
    {
        // Opay uses HMAC-SHA512 for webhook verification
        $signature = $request->header('Authorization') ?? $request->header('X-Opay-Signature');
        if (str_starts_with((string)$signature, 'Bearer ')) {
            $signature = substr($signature, 7);
        }

        $secret = config('services.opay.secret_key');

        $computed = hash_hmac('sha512', $request->getContent(), (string)$secret);

        if (!$signature || !hash_equals($signature, $computed)) {
             Log::warning('Opay webhook signature verification failed', [
                'has_header' => !empty($signature),
                'header' => $signature,
                'computed' => $computed,
                'ip' => $request->ip(),
            ]);
            // return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $payload = $request->all();
        $status = $payload['status'] ?? null;
        $reference = $payload['reference'] ?? ($payload['orderNo'] ?? null);

        if ($status !== 'SUCCESS') {
            return response()->json(['status' => 'ignored']);
        }

        if (!$reference) {
            return response()->json(['message' => 'No reference'], 400);
        }

        // Verify with Opay for extra safety
        $service = app(OpayService::class);
        $verifiedData = $service->verifyTransaction($reference);

        if (!$verifiedData || ($verifiedData['status'] ?? '') !== 'SUCCESS') {
            Log::warning('Opay verify call failed or not SUCCESS', ['reference' => $reference, 'body' => $verifiedData]);
            return response()->json(['message' => 'Verification failed'], 400);
        }

        $amountNgn = (float)($verifiedData['amount'] ?? 0) / 100; // Assuming kobo if integer
        if (isset($verifiedData['amount']['total'])) {
            $amountNgn = (float)$verifiedData['amount']['total'] / 100;
        }
        $currency = $verifiedData['currency'] ?? ($verifiedData['amount']['currency'] ?? 'NGN');

        // 1) Contribution path
        $contributions = Contribution::where('reference', $reference)
            ->where('status', 'pending')
            ->get();

        if ($contributions->isNotEmpty()) {
            $expectedTotal = (float) $contributions->sum('amount');
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < $expectedTotal) {
                Log::warning('Opay webhook: amount/currency mismatch for contributions', [
                    'reference' => $reference,
                    'paid_amount' => $amountNgn,
                    'expected' => $expectedTotal,
                    'currency' => $currency,
                ]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

            DB::transaction(function () use ($contributions) {
                foreach ($contributions as $contrib) {
                    $contrib->update(['status' => 'success']);
                }
            });

            // Notify user
            try {
                $user = User::find($contributions->first()->user_id);
                if ($user) {
                    $user->notifyMember(
                        'Payment Successful',
                        "Your payment of â‚¦" . number_format($amountNgn, 2) . " for " . $contributions->count() . " items was successful.",
                        [
                            'type' => 'payment_success',
                            'amount' => (float) $amountNgn,
                            'reference' => $reference,
                            'route' => '/pay',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send Opay contribution notification', ['error' => $e->getMessage()]);
            }

            return response()->json(['status' => 'success']);
        }

        // 2) Sadaqah path
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < (float) $sadaqahContrib->amount) {
                return response()->json(['message' => 'Amount mismatch'], 400);
            }
            if ($sadaqahContrib->status === 'success') {
                return response()->json(['status' => 'ok']);
            }
            DB::transaction(function () use ($sadaqahContrib) {
                $sadaqahContrib->update(['status' => 'success']);
                $project = SadaqahProject::lockForUpdate()->find($sadaqahContrib->sadaqah_project_id);
                if ($project) {
                    $project->increment('raised_amount', (float) $sadaqahContrib->amount);
                }
            });
            return response()->json(['status' => 'success']);
        }

        // 3) Loan Repayment path
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            if ($currency !== 'NGN' || ($amountNgn + 0.005) < (float) $loanRep->amount) {
                return response()->json(['message' => 'Amount mismatch'], 400);
            }
            if ($loanRep->status === 'success') {
                return response()->json(['status' => 'ok']);
            }
            DB::transaction(function () use ($loanRep) {
                $loan = QardHasan::lockForUpdate()->find($loanRep->qard_hasan_id);
                if ($loan) {
                    $loanRep->update(['status' => 'success', 'paid_at' => now()]);
                    $loan->increment('paid_amount', (float) $loanRep->amount);
                    if ($loan->paid_amount >= $loan->principal_amount) {
                        $loan->update(['status' => 'completed']);
                    }
                }
            });
            return response()->json(['status' => 'success']);
        }

        // 4) Wallet Top-up path
        // Opay metadata is usually in 'user_data' or custom fields
        $meta = $verifiedData['metadata'] ?? ($payload['user_data'] ?? []);
        if (is_string($meta)) { $decoded = json_decode($meta, true); if (json_last_error() === JSON_ERROR_NONE) { $meta = $decoded; } }

        $userId = $meta['user_id'] ?? null;
        $topupUser = $userId ? User::find($userId) : null;

        // DVA lookup
        if (!$topupUser) {
            $userReference = $verifiedData['userReference'] ?? null;
            if ($userReference) {
                $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('opay_user_reference', $userReference))->first();
            }
            if (!$topupUser && !empty($verifiedData['customer']['email'])) {
                $topupUser = User::where('email', $verifiedData['customer']['email'])->first();
            }
        }

        if (!$topupUser) {
            Log::warning('Opay wallet top-up: user not found', ['reference' => $reference]);
            return response()->json(['status' => 'ignored']);
        }

        if ($currency !== 'NGN' || $amountNgn <= 0) {
            return response()->json(['status' => 'ignored']);
        }

        $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
        $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

        if (WalletTransaction::where('reference', $reference)->exists()) {
            return response()->json(['status' => 'ok']);
        }

        DB::transaction(function () use ($topupUser, $amountNgn, $netAmount, $maintenanceCharge, $reference, $verifiedData) {
            $topupUser->increment('balance', $netAmount);
            WalletTransaction::create([
                'user_id' => $topupUser->id,
                'type' => 'credit',
                'amount' => $netAmount,
                'reference' => $reference,
                'source' => 'opay_topup',
                'meta' => [
                    'processor' => 'opay',
                    'maintenance_charge' => $maintenanceCharge,
                    'gross_amount' => $amountNgn,
                    'payment_method' => $verifiedData['instrumentType'] ?? null,
                ],
            ]);
        });

        $topupUser->notifyMember(
            'Wallet Top-up Successful',
            "Your wallet has been credited with â‚¦" . number_format($netAmount, 2) . " after a maintenance charge of â‚¦" . number_format($maintenanceCharge, 2) . ".",
            [
                'type' => 'wallet_topup',
                'amount' => (float) $netAmount,
                'reference' => $reference,
                'route' => '/wallet',
            ]
        );

        return response()->json(['status' => 'success']);
    }

    private function calculateMaintenanceCharge(float $amount): float
    {
        return app(AdministrativeChargeService::class)->calculateMaintenanceCharge($amount);
    }
}
