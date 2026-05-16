<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\SadaqahContribution;
use App\Models\User;
use App\Models\WebhookCall;
use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ProjectInvestment;
use App\Models\SadaqahProject;
use App\Models\ExpenseEntry;
use App\Mail\PaymentStatusMail;
use App\Mail\RepaymentReceiptUser;
use App\Notifications\PaymentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\SmsService;
use App\Services\TakafulService;
use App\Services\AdministrativeChargeService;

class WebhookProcessorService
{
    public function process(WebhookCall $webhookCall): void
    {
        $provider = $webhookCall->provider;
        $payload = $webhookCall->payload;

        try {
            DB::transaction(function () use ($provider, $payload, $webhookCall) {
                switch ($provider) {
                    case 'paystack':
                        $this->processPaystack($payload);
                        break;
                    case 'flutterwave':
                        $this->processFlutterwave($payload);
                        break;
                    case 'monnify':
                        $this->processMonnify($payload);
                        break;
                    case 'opay':
                        $this->processOpay($payload);
                        break;
                    default:
                        throw new \Exception("Unsupported provider: {$provider}");
                }

                $webhookCall->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
            }, 5); // 5 retries for deadlocks
        } catch (\Throwable $e) {
            Log::error("Webhook processing failed: " . $e->getMessage(), [
                'webhook_call_id' => $webhookCall->id,
                'provider' => $provider,
                'exception' => $e->getTraceAsString()
            ]);

            $webhookCall->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    protected function processPaystack(array $payload): void
    {
        $event = $payload['event'];
        $data = $payload['data'];
        $secret = config('services.paystack.secret_key');

        if ($event === 'charge.failed') {
            $this->handlePaystackChargeFailed($data);
            return;
        }

        if ($event === 'charge.success') {
            $this->handlePaystackChargeSuccess($data, $secret);
            return;
        }

        if (in_array($event, ['transfer.success', 'transfer.failed', 'transfer.reversed'])) {
            $this->handlePaystackTransfer($event, $data);
            return;
        }
    }

    protected function handlePaystackChargeFailed(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $amountNgn = isset($data['amount']) ? round(((int) $data['amount']) / 100, 2) : null;
        $reason = $data['gateway_response'] ?? ($data['message'] ?? 'Payment failed');

        $user = null;
        if ($reference) {
            $contrib = Contribution::where('reference', $reference)->first();
            if ($contrib) { $user = User::find($contrib->user_id); }
        }
        if (!$user) {
            $customerCode = $data['customer']['customer_code'] ?? null;
            if ($customerCode) {
                $user = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
            }
        }

        if ($user) {
            try {
                $user->notifyMember('Payment Failed', 'Your payment attempt was not successful. ' . $reason, [
                    'type' => 'payment_failed',
                    'amount' => $amountNgn,
                    'reference' => (string) ($reference ?? ''),
                    'route' => $reference && Contribution::where('reference', $reference)->exists() ? '/pay' : '/wallet',
                ], ['push', 'database', 'mail']);
            } catch (\Throwable $e) {
                Log::warning('Failed to send Paystack failure notification', ['reference' => $reference, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function handlePaystackChargeSuccess(array $data, string $secret): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        // Verify transaction with Paystack for extra safety
        $verify = Http::withToken($secret)
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 300)
            ->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));

        if (!$verify->ok() || ($verify->json('status') !== true)) {
            throw new \Exception("Paystack verification failed for reference: {$reference}");
        }

        $vd = $verify->json('data');
        if (!$vd || ($vd['status'] ?? null) !== 'success') return;

        // Check Sadaqah
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            $this->processSadaqahContribution($sadaqahContrib, $vd);
            return;
        }

        // Check Loan Repayment
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            $this->processLoanRepayment($loanRep, $vd);
            return;
        }

        // Check pending contributions
        $contributions = Contribution::where('reference', $reference)
            ->where('status', 'pending')
            ->get();

        if ($contributions->isNotEmpty()) {
            $this->processSchemeContributions($contributions, $vd);
            return;
        }

        // Default to Wallet Topup
        $this->processWalletTopup($vd);
    }

    protected function processSadaqahContribution(SadaqahContribution $sadaqahContrib, array $vd): void
    {
        if ($sadaqahContrib->status === 'success') return;

        $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);
        if ($amountNgn < (float) $sadaqahContrib->amount) {
            Log::warning('Sadaqah amount mismatch', ['ref' => $sadaqahContrib->reference, 'paid' => $amountNgn, 'expected' => $sadaqahContrib->amount]);
            return;
        }

        $sadaqahContrib->update(['status' => 'success']);
        $project = SadaqahProject::find($sadaqahContrib->sadaqah_project_id);
        if ($project) {
            $project->increment('raised_amount', $sadaqahContrib->amount);
        }

        $user = User::find($sadaqahContrib->user_id);
        if ($user) {
            $user->notifyMember(
                'Sadaqah Contribution Successful',
                "Your contribution of ₦" . number_format($sadaqahContrib->amount, 2) . " to " . ($project->name ?? 'Project') . " was successful.",
                ['type' => 'sadaqah_contribution', 'amount' => (float)$sadaqahContrib->amount, 'route' => '/sadaqah']
            );
        }
    }

    protected function processLoanRepayment(QardHasanRepayment $loanRep, array $vd): void
    {
        if ($loanRep->status === 'success') return;

        $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);
        if ($amountNgn < (float) $loanRep->amount) return;

        $loan = QardHasan::lockForUpdate()->find($loanRep->qard_hasan_id);
        if ($loan) {
            $loanRep->update(['status' => 'success', 'paid_at' => now()]);
            $loan->increment('paid_amount', (float) $loanRep->amount);
            if ($loan->paid_amount >= $loan->principal_amount) {
                $loan->update(['status' => 'completed']);
            }

            if ($loan->user) {
                $remaining = max(0, (float) $loan->principal_amount - (float) $loan->paid_amount);
                $loan->user->notifyMember(
                    'Repayment Received',
                    'Loan repayment received: ₦'.number_format((float)$loanRep->amount, 2).'. Remaining: ₦'.number_format($remaining, 2),
                    ['type' => 'loan_repayment', 'loan_id' => $loan->id, 'route' => '/loan/' . $loan->id]
                );
            }
        }
    }

    protected function processSchemeContributions($contributions, array $vd): void
    {
        $expectedTotal = (float) $contributions->sum('amount');
        $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);

        if ($amountNgn < $expectedTotal) return;

        $userId = $contributions->first()->user_id;
        $user = User::find($userId);

        foreach ($contributions as $contribution) {
            if ($contribution->status === 'success') continue;
            $contribution->update(['status' => 'success']);

            $schemeName = $contribution->scheme?->name;
            if ($schemeName && in_array($schemeName, ['Zakat', 'Zakat Al-Fitr'])) {
                $this->handleZakatContribution($contribution, $user, $schemeName);
            }
        }

        if ($user) {
            $user->notifyMember(
                'Payment Successful',
                'Your payment of ₦' . number_format($expectedTotal, 2) . ' has been received.',
                ['type' => 'scheme_payment', 'amount' => (float)$expectedTotal, 'route' => '/passbook']
            );
        }
    }

    protected function handleZakatContribution($contribution, $user, $schemeName): void
    {
        \App\Models\CharityEntry::create([
            'user_id' => $contribution->user_id,
            'source' => $schemeName,
            'amount' => $contribution->amount,
            'note' => "Payment for {$schemeName} (Ref: {$contribution->reference})",
        ]);

        $zakatProject = SadaqahProject::firstOrCreate(
            ['name' => 'General Zakat Fund'],
            ['description' => 'Automated Zakat Fund', 'active' => true]
        );

        $zakatProject->increment('raised_amount', $contribution->amount);

        if ($schemeName === 'Zakat' && $user) {
            $user->update([
                'zakat_last_paid_at' => now(),
                'zakat_nisab_crossed_at' => now(),
            ]);
        }
    }

    protected function processWalletTopup(array $vd): void
    {
        $customerCode = $vd['customer']['customer_code'] ?? null;
        $receiverAccount = $vd['authorization']['receiver_bank_account_number'] ?? ($vd['authorization']['account_number'] ?? null);

        $topupUser = null;
        if ($customerCode) {
            $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('paystack_customer_code', $customerCode))->first();
        }
        if (!$topupUser && $receiverAccount) {
            $topupUser = User::whereHas('virtualAccount', fn($q) => $q->where('dva_account_number', $receiverAccount))->first();
        }

        if (!$topupUser) return;

        $amountNgn = round(((int) ($vd['amount'] ?? 0)) / 100, 2);
        $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
        $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

        if (WalletTransaction::where('reference', $vd['reference'])->exists()) return;

        $topupUser->increment('balance', $netAmount);

        WalletTransaction::create([
            'user_id' => $topupUser->id,
            'type' => 'credit',
            'amount' => $netAmount,
            'reference' => $vd['reference'],
            'source' => 'paystack',
            'meta' => ['maintenance_charge' => $maintenanceCharge, 'gross_amount' => $amountNgn]
        ]);

        $topupUser->notifyMember(
            'Wallet Top-up Successful',
            "Your wallet has been credited with ₦" . number_format($netAmount, 2),
            ['type' => 'wallet_topup', 'amount' => (float)$netAmount, 'route' => '/wallet']
        );
    }

    protected function handlePaystackTransfer(string $event, array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        $expense = ExpenseEntry::where('payout_reference', $reference)->first();
        if (!$expense) return;

        if ($event === 'transfer.success') {
            $expense->update(['status' => 'processed', 'processed_at' => now()]);
        } elseif ($event === 'transfer.failed' || $event === 'transfer.reversed') {
            $reason = $data['reason'] ?? 'Unknown error';
            $expense->update(['status' => 'approved', 'rejection_reason' => "Transfer failed: " . $reason]);
        }
    }

    protected function calculateMaintenanceCharge(float $amount): float
    {
        // Simple implementation, should match Controller
        if ($amount <= 5000) return 10.0;
        if ($amount <= 50000) return 25.0;
        return 50.0;
    }

    protected function processFlutterwave(array $payload): void
    {
        $data = $payload['data'] ?? $payload;
        $reference = $data['tx_ref'] ?? $data['txRef'] ?? null;
        $status = strtolower((string)($data['status'] ?? ''));
        $txId = $data['id'] ?? null;

        if (!$reference) return;

        $secret = config('services.flutterwave.secret_key');

        $verify = null;
        if (!empty($txId)) {
            $verify = Http::withToken($secret)->timeout(15)->retry(3, 300)
                ->get('https://api.flutterwave.com/v3/transactions/' . urlencode((string)$txId) . '/verify');
        } else {
            $verify = Http::withToken($secret)->timeout(15)->retry(3, 300)
                ->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', ['tx_ref' => $reference]);
        }

        if (!$verify->ok() || ($verify->json('status') !== 'success')) {
            throw new \Exception("Flutterwave verification failed for reference: {$reference}");
        }

        $vd = $verify->json('data');
        if (!$vd || strtolower((string)($vd['status'] ?? '')) !== 'successful') {
            if (in_array(strtolower((string)($vd['status'] ?? '')), ['failed', 'cancelled', 'error'])) {
                $this->handleFlutterwaveFailure($vd, $reference);
            }
            return;
        }

        $amountNgn = (float) ($vd['charged_amount'] ?? $vd['amount'] ?? 0);

        // Check Sadaqah
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            $this->processSadaqahContribution($sadaqahContrib, ['amount' => $amountNgn * 100]); // processSadaqahContribution expects kobo in its Paystack-based logic, let's normalize
            return;
        }

        // Check Loan Repayment
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            $this->processLoanRepayment($loanRep, ['amount' => $amountNgn * 100]);
            return;
        }

        // Check pending contributions
        $contributions = Contribution::where('reference', $reference)->where('status', 'pending')->get();
        if ($contributions->isNotEmpty()) {
            $this->processSchemeContributions($contributions, ['amount' => $amountNgn * 100]);
            return;
        }

        // Wallet Topup
        $this->processWalletTopupGeneric($vd, $reference, $amountNgn, 'flutterwave');
    }

    protected function handleFlutterwaveFailure(array $vd, string $reference): void
    {
        $user = null;
        $contrib = Contribution::where('reference', $reference)->first();
        if ($contrib) { $user = User::find($contrib->user_id); }

        if ($user) {
            $reason = $vd['processor_response'] ?? 'Payment failed';
            $user->notifyMember('Payment Failed', 'Your payment attempt was not successful. ' . $reason, [
                'type' => 'payment_failed',
                'amount' => (float) ($vd['charged_amount'] ?? $vd['amount'] ?? 0),
                'reference' => (string) $reference,
                'route' => $contrib ? '/pay' : '/wallet',
            ], ['push', 'database', 'mail']);
        }
    }

    protected function processMonnify(array $payload): void
    {
        $data = $payload['eventData'] ?? [];
        $reference = $data['paymentReference'] ?? null;
        if (!$reference) return;

        $service = app(MonnifyService::class);
        $vd = $service->verifyTransaction($reference);

        if (!$vd || ($vd['paymentStatus'] ?? '') !== 'PAID') return;

        $amountNgn = (float)($vd['amountPaid'] ?? 0);

        // Check Sadaqah
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            $this->processSadaqahContribution($sadaqahContrib, ['amount' => $amountNgn * 100]);
            return;
        }

        // Check Loan Repayment
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            $this->processLoanRepayment($loanRep, ['amount' => $amountNgn * 100]);
            return;
        }

        // Check contributions
        $contributions = Contribution::where('reference', $reference)->where('status', 'pending')->get();
        if ($contributions->isNotEmpty()) {
            $this->processSchemeContributions($contributions, ['amount' => $amountNgn * 100]);
            return;
        }

        // Wallet Topup
        $this->processWalletTopupGeneric($vd, $reference, $amountNgn, 'monnify');
    }

    protected function processOpay(array $payload): void
    {
        $reference = $payload['reference'] ?? ($payload['orderNo'] ?? null);
        if (!$reference) return;

        $service = app(OpayService::class);
        $vd = $service->verifyTransaction($reference);

        if (!$vd || ($vd['status'] ?? '') !== 'SUCCESS') return;

        $amountNgn = (float)($vd['amount'] ?? 0) / 100;
        if (isset($vd['amount']['total'])) {
            $amountNgn = (float)$vd['amount']['total'] / 100;
        }

        // Check Sadaqah
        $sadaqahContrib = SadaqahContribution::where('reference', $reference)->first();
        if ($sadaqahContrib) {
            $this->processSadaqahContribution($sadaqahContrib, ['amount' => $amountNgn * 100]);
            return;
        }

        // Check Loan Repayment
        $loanRep = QardHasanRepayment::where('reference', $reference)->first();
        if ($loanRep) {
            $this->processLoanRepayment($loanRep, ['amount' => $amountNgn * 100]);
            return;
        }

        // Check contributions
        $contributions = Contribution::where('reference', $reference)->where('status', 'pending')->get();
        if ($contributions->isNotEmpty()) {
            $this->processSchemeContributions($contributions, ['amount' => $amountNgn * 100]);
            return;
        }

        // Wallet Topup
        $this->processWalletTopupGeneric($vd, $reference, $amountNgn, 'opay');
    }

    protected function processWalletTopupGeneric(array $vd, string $reference, float $amountNgn, string $provider): void
    {
        if (WalletTransaction::where('reference', $reference)->exists()) return;

        $topupUser = null;
        if ($provider === 'flutterwave') {
            $email = $vd['customer']['email'] ?? null;
            if ($email) $topupUser = User::where('email', $email)->first();
        } elseif ($provider === 'monnify') {
            $email = $vd['customer']['email'] ?? null;
            if ($email) $topupUser = User::where('email', $email)->first();
        } elseif ($provider === 'opay') {
            // Opay logic usually has user info in metadata or reference
        }

        if (!$topupUser) {
             // Fallback to meta if available
             $meta = $vd['meta'] ?? ($vd['metadata'] ?? []);
             if (is_string($meta)) $meta = json_decode($meta, true);
             $userId = $meta['user_id'] ?? null;
             if ($userId) $topupUser = User::find($userId);
        }

        if (!$topupUser) return;

        $maintenanceCharge = $this->calculateMaintenanceCharge($amountNgn);
        $netAmount = round(max(0, $amountNgn - $maintenanceCharge), 2);

        $topupUser->increment('balance', $netAmount);

        WalletTransaction::create([
            'user_id' => $topupUser->id,
            'type' => 'credit',
            'amount' => $netAmount,
            'reference' => $reference,
            'source' => $provider,
            'meta' => ['maintenance_charge' => $maintenanceCharge, 'gross_amount' => $amountNgn]
        ]);

        $topupUser->notifyMember(
            'Wallet Top-up Successful',
            "Your wallet has been credited with ₦" . number_format($netAmount, 2),
            ['type' => 'wallet_topup', 'amount' => (float)$netAmount, 'route' => '/wallet']
        );
    }
}
