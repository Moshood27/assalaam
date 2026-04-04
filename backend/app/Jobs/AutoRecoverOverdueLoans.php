<?php

namespace App\Jobs;

use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoRecoverOverdueLoans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user) return;

            // Quick exit if balance is zero or less
            if ((float) $user->balance <= 0) return;

            $totalApplied = 0.0;
            $appliedLoans = [];

            // Process each active loan in order of soonest next due
            $loans = QardHasan::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            if ($loans->isEmpty()) return;

            foreach ($loans as $loan) {
                // Compute overdue amount for this loan
                $overdue = $this->computeOverdueAmount($loan);
                if ($overdue <= 0) continue;

                // Attempt to apply from wallet within a transaction with row locks
                $applied = DB::transaction(function () use ($user, $loan, $overdue) {
                    // Lock rows to ensure consistency and avoid double charges
                    $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
                    /** @var QardHasan $lockedLoan */
                    $lockedLoan = QardHasan::whereKey($loan->id)->lockForUpdate()->first();

                    // Recalculate within lock window
                    $freshOverdue = $this->computeOverdueAmount($lockedLoan);
                    if ($freshOverdue <= 0) {
                        return 0.0;
                    }

                    $wallet = (float) $lockedUser->balance;
                    if ($wallet <= 0) {
                        return 0.0;
                    }

                    $apply = round(min($freshOverdue, $wallet), 2);
                    if ($apply <= 0) {
                        return 0.0;
                    }

                    // Create repayment record with a unique reference
                    $reference = 'QHHUNT-' . now()->format('YmdHis') . '-' . $lockedUser->id . '-' . Str::upper(Str::random(5));

                    QardHasanRepayment::create([
                        'qard_hasan_id' => $lockedLoan->id,
                        'amount' => $apply,
                        'reference' => $reference,
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);

                    // Deduct wallet and record a debit transaction
                    $lockedUser->decrement('balance', $apply);
                    WalletTransaction::create([
                        'user_id' => $lockedUser->id,
                        'type' => 'debit',
                        'amount' => $apply,
                        'reference' => $reference,
                        'source' => 'loan_repayment',
                        'meta' => [
                            'auto_hunter' => true,
                            'qard_hasan_id' => $lockedLoan->id,
                            'qard_id_string' => $lockedLoan->qard_id_string,
                        ],
                    ]);

                    // Update aggregates on the loan
                    $lockedLoan->paid_amount = (float) $lockedLoan->paid_amount + $apply;
                    if ($lockedLoan->paid_amount >= (float) $lockedLoan->principal_amount) {
                        $lockedLoan->status = 'completed';
                    }
                    $lockedLoan->save();

                    return $apply;
                });

                if ($applied > 0) {
                    $totalApplied += $applied;
                    $appliedLoans[] = $loan->id;
                }

                // Stop if wallet is exhausted
                $user->refresh();
                if ((float) $user->balance <= 0) {
                    break;
                }
            }

            if ($totalApplied > 0) {
                // Best-effort push notification to the user
                try {
                    /** @var PushService $push */
                    $push = app(PushService::class);
                    $token = $user->fcm_token ?: ($user->device_token ?? null);
                    $title = 'Auto Loan Repayment';
                    $body = 'We noticed a deposit and have automatically applied it to your overdue loan.';
                    $push->send($token, $title, $body, [
                        'type' => 'loan_auto_recovery',
                        'applied_total' => number_format($totalApplied, 2, '.', ''),
                        'loan_ids' => $appliedLoans,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('AutoRecoverOverdueLoans push failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AutoRecoverOverdueLoans failed', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Compute the overdue amount for a loan at the current moment.
     */
    protected function computeOverdueAmount(QardHasan $loan): float
    {
        if ($loan->status !== 'active') return 0.0;
        $per = (float) $loan->per_installment;
        if ($per <= 0) {
            $per = round(((float)$loan->principal_amount) / max((int)$loan->total_installments, 1), 2);
        }
        if ($per <= 0) return 0.0;

        $schedule = $loan->generateInstallmentSchedule();
        if (empty($schedule)) return 0.0;

        $now = Carbon::now();
        $dueCount = 0;
        foreach ($schedule as $item) {
            $dueAt = $item['due_at'] instanceof Carbon ? $item['due_at'] : Carbon::parse((string) $item['due_at']);
            if ($dueAt->lessThanOrEqualTo($now)) {
                $dueCount++;
            } else {
                break; // schedule is in order; stop at first future item
            }
        }
        if ($dueCount <= 0) return 0.0;

        $expectedPaid = round(min($dueCount * $per, (float) $loan->principal_amount), 2);
        $alreadyPaid = (float) $loan->paid_amount;
        $overdue = round(max(0.0, $expectedPaid - $alreadyPaid), 2);

        // Never exceed remaining principal
        $remaining = max(0.0, (float) $loan->principal_amount - $alreadyPaid);
        if ($overdue > $remaining) $overdue = $remaining;
        return $overdue;
    }
}
