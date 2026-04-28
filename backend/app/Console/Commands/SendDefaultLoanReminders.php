<?php

namespace App\Console\Commands;

use App\Mail\DefaultLoanReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDefaultLoanReminders extends Command
{
    protected $signature = 'loans:send-default-reminders {--dry-run : Show what would be sent without sending emails}';

    protected $description = 'Send reminder emails to all defaulters with outstanding active loans';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $countUsers = 0;
        $countEmails = 0;
        $countFlagged = 0;

        // 1. Identify and flag NEW defaulters
        $this->syncMissingPenalties($dry);

        $activeLoans = \App\Models\QardHasan::whereIn('status', ['active', 'defaulted'])
            ->whereNull('defaulted_at')
            ->whereColumn('paid_amount', '<', 'principal_amount')
            ->get();

        foreach ($activeLoans as $loan) {
            if ($loan->getOverdueDays() >= 7) { // 7 days threshold
                $countFlagged++;
                if (!$dry) {
                    $loan->update(['defaulted_at' => now()]);
                    $this->info("Flagged loan {$loan->qard_id_string} for member {$loan->user?->name} as defaulted (Overdue {$loan->getOverdueDays()} days).");
                } else {
                    $this->info("[DRY] Would flag loan {$loan->qard_id_string} as defaulted.");
                }
            }
        }

        // 2. Clear default flag if loan is NO LONGER overdue
        $defaultedLoans = \App\Models\QardHasan::whereIn('status', ['active', 'defaulted'])
            ->whereNotNull('defaulted_at')
            ->where('defaulted_at', '<=', now())
            ->get();
        foreach ($defaultedLoans as $loan) {
            if ($loan->getOverdueAmount() <= 0) {
                if (!$dry) {
                    $loan->update(['defaulted_at' => null]);
                    $this->info("Cleared default flag for loan {$loan->qard_id_string} (No longer overdue).");
                }
            }
        }

        // 3. Send reminders to current defaulters
        $users = User::query()
            ->where('is_defaulter', true)
            ->whereNotNull('email')
            ->with(['qardHasans' => function ($q) {
                $q->whereIn('status', ['active', 'pending', 'defaulted']);
            }])
            ->get();

        foreach ($users as $user) {
            $loansData = [];
            $totalOutstanding = 0.0;

            foreach ($user->qardHasans as $loan) {
                $remaining = max((float) $loan->principal_amount - (float) $loan->paid_amount, 0);
                if ($remaining <= 0) {
                    continue;
                }
                $loansData[] = [
                    'loan_id' => $loan->qard_id_string ?: ('QH-' . $loan->id),
                    'status' => $loan->status,
                    'principal' => (float) $loan->principal_amount,
                    'paid' => (float) $loan->paid_amount,
                    'remaining' => $remaining,
                ];
                $totalOutstanding += $remaining;
            }

            if (empty($loansData)) {
                continue;
            }

            $countUsers++;
            if ($dry) {
                $this->info(sprintf('[DRY] Would send to %s <%s> | Loans: %d | Outstanding: %.2f', $user->name, $user->email, count($loansData), $totalOutstanding));
                continue;
            }

            Mail::to($user->email)->send(new DefaultLoanReminder($user, $loansData, $totalOutstanding));
            $countEmails++;
            $this->info(sprintf('Sent reminder to %s <%s> | Loans: %d | Outstanding: %.2f', $user->name, $user->email, count($loansData), $totalOutstanding));
        }

        $this->info(sprintf('Completed. Defaulters checked: %d, Flagged: %d, Emails sent: %d', $countUsers, $countFlagged, $countEmails));
        return self::SUCCESS;
    }

    protected function syncMissingPenalties(bool $dry): void
    {
        // Ensure all currently defaulted loans have an open penalty record
        $defaultedLoans = \App\Models\QardHasan::whereNotNull('defaulted_at')->get();
        foreach ($defaultedLoans as $loan) {
            $exists = \App\Models\LoanPenalty::where('qard_hasan_id', $loan->id)
                ->whereNull('default_cleared_at')
                ->exists();

            if (!$exists) {
                if (!$dry) {
                    $loan->startPenaltyRecord();
                    $this->info("Created missing penalty record for loan {$loan->qard_id_string}");
                } else {
                    $this->info("[DRY] Would create missing penalty record for loan {$loan->qard_id_string}");
                }
            }
        }

        // Ensure all cleared defaults have their penalty records completed
        $activePenalties = \App\Models\LoanPenalty::whereNull('default_cleared_at')->get();
        foreach ($activePenalties as $penalty) {
            $loan = $penalty->qardHasan;
            if (!$loan || !$loan->defaulted_at) {
                if (!$dry) {
                    if ($loan) {
                        $loan->completePenaltyRecord();
                        $this->info("Completed stray penalty record for loan {$loan->qard_id_string}");
                    } else {
                        $penalty->update(['default_cleared_at' => now(), 'penalty_until' => now()]);
                    }
                }
            }
        }
    }
}
