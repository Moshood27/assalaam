<?php

namespace App\Services;

use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\TakafulContribution;
use App\Models\TakafulPoolEntry;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TakafulService
{
    public function monthlyAmount(): float
    {
        return app(\App\Services\AdministrativeChargeService::class)->getCharge(
            'takaful_monthly_contribution',
            (float) config('services.takaful.monthly_amount', 200.00)
        );
    }

    /**
     * Charge monthly Takaful contributions.
     * - period format: YYYY-MM
     * - If userId is provided, only process that user.
     * - If dryRun, return a summary without writing.
     */
    public function chargeMonthly(?string $period = null, ?float $amount = null, ?int $userId = null, bool $dryRun = false): array
    {
        $period = $period ?: now()->format('Y-m');
        $amount = $amount !== null ? (float) $amount : $this->monthlyAmount();

        $query = User::query();
        // Exclude admin accounts by default
        $query->where(function ($q) {
            $q->whereNull('is_admin')->orWhere('is_admin', false);
        });
        // Do not charge members marked deceased or exempted by policy
        $query->whereNull('deceased_at');
        $query->where(function ($w) {
            $w->whereNull('takaful_exempt')->orWhere('takaful_exempt', false);
        });
        if ($userId) {
            $query->whereKey($userId);
        }

        $totals = [
            'period' => $period,
            'amount' => $amount,
            'processed' => 0,
            'charged' => 0.0,
            'skipped_existing' => 0,
            'insufficient_funds' => 0,
            'created' => 0,
        ];

        $users = $query->get();
        foreach ($users as $user) {
            // If already has a successful contribution for this period, skip
            $existing = TakafulContribution::where('user_id', $user->id)
                ->where('period', $period)
                ->where('status', 'success')
                ->first();
            if ($existing) {
                $totals['skipped_existing']++;

                continue;
            }

            $totals['processed']++;

            if ($dryRun) {
                // Policy change: Everyone eligible gets a pending record (dry-run count)
                $totals['created']++;

                continue;
            }

            DB::transaction(function () use ($user, $period, $amount, &$totals) {
                // Lock user
                /** @var User $locked */
                $locked = User::whereKey($user->id)->lockForUpdate()->first();

                // Ensure we still don't have a successful record
                $exists = TakafulContribution::where('user_id', $locked->id)
                    ->where('period', $period)
                    ->where('status', 'success')
                    ->exists();
                if ($exists) {
                    $totals['skipped_existing']++;

                    return;
                }

                // Policy change: Remove automatic debit. Mark as pending for manual payment.
                TakafulContribution::updateOrCreate(
                    ['user_id' => $locked->id, 'period' => $period],
                    [
                        'amount' => $amount,
                        'status' => 'pending',
                        'meta' => [
                            'reason' => 'manual_payment_policy',
                        ],
                    ]
                );

                $totals['created']++;
            });
        }

        $totals['balance'] = TakafulPoolEntry::balance();

        return $totals;
    }

    /**
     * Charge a single member immediately for a given period (default: current month).
     * Returns array with status: success | already_paid | insufficient_funds
     */
    public function payNow(User $user, ?string $period = null, ?float $amount = null): array
    {
        $period = $period ?: now()->format('Y-m');
        $amount = $amount !== null ? (float) $amount : $this->monthlyAmount();

        // If already paid
        $existing = TakafulContribution::where('user_id', $user->id)
            ->where('period', $period)
            ->where('status', 'success')
            ->first();
        if ($existing) {
            return [
                'status' => 'already_paid',
                'period' => $period,
                'amount' => (float) $existing->amount,
                'reference' => $existing->reference,
                'balance' => (float) $user->balance,
            ];
        }

        $result = [
            'status' => 'insufficient_funds',
            'period' => $period,
            'amount' => $amount,
            'reference' => null,
            'balance' => (float) $user->balance,
        ];

        DB::transaction(function () use ($user, $period, $amount, &$result) {
            /** @var User $locked */
            $locked = User::whereKey($user->id)->lockForUpdate()->first();

            // Check again inside txn
            $exists = TakafulContribution::where('user_id', $locked->id)
                ->where('period', $period)
                ->where('status', 'success')
                ->exists();
            if ($exists) {
                $paid = TakafulContribution::where('user_id', $locked->id)
                    ->where('period', $period)
                    ->where('status', 'success')
                    ->first();
                $result['status'] = 'already_paid';
                $result['amount'] = (float) ($paid?->amount ?? $amount);
                $result['reference'] = $paid?->reference;
                $result['balance'] = (float) $locked->balance;

                return;
            }

            if ((float) $locked->balance < $amount) {
                // Create or update pending marker
                TakafulContribution::updateOrCreate(
                    ['user_id' => $locked->id, 'period' => $period],
                    [
                        'amount' => $amount,
                        'status' => 'pending',
                        'meta' => ['reason' => 'insufficient_wallet_balance'],
                    ]
                );
                $result['status'] = 'insufficient_funds';
                $result['balance'] = (float) $locked->balance;

                return;
            }

            // Proceed to debit wallet and credit pool
            $reference = 'TAKAFUL_CONTR_'.now()->format('YmdHis').'_'.$locked->id.'_'.bin2hex(random_bytes(3));
            $locked->balance -= $amount;
            $locked->save();

            WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'source' => 'takaful_contribution',
                'meta' => ['period' => $period, 'initiated_by' => 'member_manual'],
            ]);

            TakafulContribution::updateOrCreate(
                ['user_id' => $locked->id, 'period' => $period],
                [
                    'amount' => $amount,
                    'status' => 'success',
                    'reference' => $reference,
                ]
            );

            TakafulPoolEntry::create([
                'user_id' => $locked->id,
                'direction' => 'credit',
                'amount' => $amount,
                'reference' => $reference,
                'meta' => ['period' => $period, 'initiated_by' => 'member_manual'],
            ]);

            $result['status'] = 'success';
            $result['reference'] = $reference;
            $result['balance'] = (float) $locked->balance; // after debit
        });

        $result['pool_balance'] = TakafulPoolEntry::balance();

        return $result;
    }

    /**
     * Settle a user's active QardHasan loans from the pool.
     * Returns a summary of settlements.
     */
    public function settleMemberLoans(User $user, string $reason = 'deceased'): array
    {
        $reason = in_array($reason, ['deceased', 'major_loss'], true) ? $reason : 'other';

        $activeLoans = QardHasan::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $poolBalance = TakafulPoolEntry::balance();
        $summary = [
            'user_id' => $user->id,
            'loans' => [],
            'total_settled' => 0.0,
            'pool_before' => $poolBalance,
            'pool_after' => $poolBalance,
        ];

        foreach ($activeLoans as $loan) {
            $remaining = max(0.0, (float) $loan->principal_amount - (float) $loan->paid_amount);
            if ($remaining <= 0) {
                continue;
            }

            // Recalculate pool balance each iteration to reflect debits
            $poolBalance = TakafulPoolEntry::balance();
            if ($poolBalance < $remaining) {
                // Not enough funds to settle this loan now
                $summary['loans'][] = [
                    'qard_id' => $loan->qard_id_string,
                    'remaining' => $remaining,
                    'status' => 'skipped_insufficient_pool',
                ];

                continue;
            }

            DB::transaction(function () use ($loan, $user, $reason, &$summary) {
                /** @var QardHasan $locked */
                $locked = QardHasan::whereKey($loan->id)->lockForUpdate()->first();
                $stillRemaining = max(0.0, (float) $locked->principal_amount - (float) $locked->paid_amount);
                if ($stillRemaining <= 0) {
                    return;
                }

                $reference = 'TAKAFUL_PAYOUT_'.$locked->id.'_'.Str::upper(Str::random(6));

                // Create repayment record (mark success)
                QardHasanRepayment::create([
                    'qard_hasan_id' => $locked->id,
                    'amount' => $stillRemaining,
                    'reference' => $reference,
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                // Update loan
                $locked->paid_amount = (float) $locked->paid_amount + (float) $stillRemaining;
                if ($locked->paid_amount >= $locked->principal_amount) {
                    $locked->status = 'completed';
                }
                $locked->save();

                // Debit pool
                TakafulPoolEntry::create([
                    'user_id' => $user->id,
                    'direction' => 'debit',
                    'amount' => $stillRemaining,
                    'reference' => $reference,
                    'meta' => [
                        'qard_id' => $locked->id,
                        'qard_code' => $locked->qard_id_string,
                        'reason' => $reason,
                    ],
                ]);

                ShariahAudit::log(null, 'takaful_settlement', [
                    'user_id' => $user->id,
                    'qard_code' => $locked->qard_id_string,
                    'amount' => $stillRemaining,
                    'reason' => $reason,
                    'reference' => $reference,
                ]);

                // Best-effort notifications to guarantors (policy-dependent)
                try {
                    $notifyEnabled = (bool) config('services.takaful.notify_contacts', true);
                    $memberAllows = (bool) ($user->takaful_notify_contacts ?? true);
                    if ($notifyEnabled && $memberAllows) {
                        $guarantors = $locked->guarantors()->get();
                        foreach ($guarantors as $g) {
                            // Push notification if possible
                            try {
                                $push = app(PushService::class);
                                $token = $g->fcm_token ?: ($g->device_token ?? null);
                                $push->send($token, 'Takaful Settlement', 'Loan '.$locked->qard_id_string.' was settled from the welfare pool due to '.$reason.'.', [
                                    'type' => 'takaful_settlement',
                                    'qard_code' => $locked->qard_id_string,
                                    'amount' => (float) $stillRemaining,
                                ]);
                            } catch (\Throwable $e) {
                            }
                            // Best-effort SMS
                            try {
                                $sms = app(SmsService::class);
                                $msg = 'Notice: Loan '.$locked->qard_id_string.' settled from Takaful ('.$reason.'). Amount: ₦'.number_format($stillRemaining, 2);
                                $sms->send($g->phone ?? null, $msg);
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore notification errors
                }

                $summary['loans'][] = [
                    'qard_id' => $locked->qard_id_string,
                    'settled' => $stillRemaining,
                    'status' => 'settled',
                ];
                $summary['total_settled'] += $stillRemaining;
            });
        }

        $summary['pool_after'] = TakafulPoolEntry::balance();

        return $summary;
    }
}
