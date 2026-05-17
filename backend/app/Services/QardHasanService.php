<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QardHasanService
{
    /**
     * Sync loan repayments from successful contributions of the 'Loan Repayment' scheme.
     */
    public function syncRepaymentsFromContributions(?User $user = null): array
    {
        $scheme = Scheme::where('name', 'Loan Repayment')->first();
        if (!$scheme) {
            return ['error' => 'Scheme "Loan Repayment" not found.'];
        }

        $query = Contribution::where('scheme_id', $scheme->id)
            ->where('status', 'success');

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $contributions = $query->orderBy('created_at', 'asc')->get();
        $processed = 0;
        $skipped = 0;

        foreach ($contributions as $contribution) {
            try {
                $res = DB::transaction(function () use ($contribution) {
                    // Check if already processed
                    if (QardHasanRepayment::where('reference', $contribution->reference)->exists()) {
                        return 'skipped';
                    }

                    // Find active or defaulted loan for the user
                    $loan = QardHasan::where('user_id', $contribution->user_id)
                        ->whereIn('status', ['active', 'defaulted'])
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->first();

                    if (!$loan) {
                        return 'no_loan';
                    }

                    // Create repayment
                    QardHasanRepayment::create([
                        'qard_hasan_id' => $loan->id,
                        'amount' => $contribution->amount,
                        'reference' => $contribution->reference,
                        'status' => 'success',
                        'paid_at' => $contribution->updated_at ?? $contribution->created_at,
                        'ledger_journal_id' => $contribution->ledger_journal_id,
                    ]);

                    return 'processed';
                });

                if ($res === 'processed') {
                    $processed++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync contribution {$contribution->id} to loan: " . $e->getMessage());
                $skipped++;
            }
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }

    /**
     * Recalculate paid_amount for all or a specific user's loans.
     */
    public function recalculateLoanBalances(?User $user = null): int
    {
        $query = QardHasan::query();

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $loans = $query->get();
        $updatedCount = 0;

        foreach ($loans as $loan) {
            $oldAmount = (float) $loan->paid_amount;
            $newAmount = $loan->recalculatePaidAmount();

            if ($oldAmount !== $newAmount) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Full sync and balance for the system or a specific user.
     */
    public function fullSyncAndBalance(?User $user = null): array
    {
        $syncResult = $this->syncRepaymentsFromContributions($user);
        $recalcCount = $this->recalculateLoanBalances($user);

        // Also reset legacy loan_repayment_balance if we synced successfully
        if ($user) {
            if ($user->loan_repayment_balance > 0) {
                 $user->forceFill(['loan_repayment_balance' => 0])->save();
            }
        } else {
            User::where('loan_repayment_balance', '>', 0)->update(['loan_repayment_balance' => 0]);
        }

        return array_merge($syncResult, ['recalculated' => $recalcCount]);
    }
}
