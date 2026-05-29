<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\LoanPaymentReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLoanMonthlyReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:send-monthly-reminders {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily reminders to members who have loan payments due this month';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // We find users who have an active or defaulted loan and haven't fully paid yet
        $users = User::whereHas('qardHasans', function ($query) {
            $query->whereIn('status', ['active', 'defaulted'])
                ->whereColumn('paid_amount', '<', 'principal_amount');
        })->get();

        $count = 0;
        foreach ($users as $user) {
            $expectedToPay = (float) $user->totalExpectedAmountToPay();

            // Only send reminder if they have something to pay to-date or for the next installment
            if ($expectedToPay > 0) {
                // Find next due date for the message
                $activeLoan = $user->qardHasans()
                    ->whereIn('status', ['active', 'defaulted'])
                    ->whereColumn('paid_amount', '<', 'principal_amount')
                    ->orderBy('created_at', 'asc')
                    ->first();

                $dueDate = $activeLoan?->next_due_at;
                $dueDateText = $dueDate ? Carbon::parse($dueDate)->format('d M, Y') : 'this month';

                if ($dryRun) {
                    $this->info("DRY RUN: Would send reminder to {$user->full_name} ({$user->email}) for amount â‚¦" . number_format($expectedToPay, 2) . " due by {$dueDateText}");
                } else {
                    $user->notify(new LoanPaymentReminder($expectedToPay, $dueDateText));
                    $this->info("Sent reminder to {$user->full_name} ({$user->email}) for amount â‚¦" . number_format($expectedToPay, 2));
                }
                $count++;
            }
        }

        $this->info("Completed. $count reminders processed.");
        return Command::SUCCESS;
    }
}
