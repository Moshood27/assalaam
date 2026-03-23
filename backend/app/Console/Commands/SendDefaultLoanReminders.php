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

        $users = User::query()
            ->where('is_defaulter', true)
            ->whereNotNull('email')
            ->with(['qardHasans' => function ($q) {
                $q->whereIn('status', ['active', 'pending']);
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

        $this->info(sprintf('Completed. Defaulters checked: %d, Emails sent: %d', $countUsers, $countEmails));
        return self::SUCCESS;
    }
}
