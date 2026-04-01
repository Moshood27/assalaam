<?php

namespace App\Console\Commands;

use App\Models\QardHasan;
use Illuminate\Console\Command;

class SendGuarantorReminders extends Command
{
    protected $signature = 'loans:remind-guarantors {--dry-run : Output targets without sending push notifications}';

    protected $description = 'Send push reminders to guarantors with pending decisions for all pending loan requests';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $countLoans = 0;
        $countPushes = 0;

        $loans = QardHasan::query()
            ->with(['guarantors' => function ($q) {
                $q->wherePivot('status', 'pending');
            }, 'user'])
            ->where('status', 'pending')
            ->get();

        $push = app(\App\Services\PushService::class);

        foreach ($loans as $loan) {
            $pending = $loan->guarantors->filter(fn($g) => ($g->pivot?->status) === 'pending');
            if ($pending->isEmpty()) {
                continue;
            }
            $countLoans++;

            $title = 'Guarantor Reminder';
            $body = 'Please review loan '.($loan->qard_id_string).' for '.($loan->user?->name).'. Accept or Decline in the app.';
            $data = [
                'type' => 'guarantor_reminder',
                'loan_id' => $loan->id,
                'qard_id_string' => $loan->qard_id_string,
            ];

            foreach ($pending as $g) {
                $token = $g->fcm_token ?: ($g->device_token ?? null);
                if ($dry) {
                    $this->info(sprintf('[DRY] Would push to %s (ID %d) for loan %s', $g->name, $g->id, $loan->qard_id_string));
                    continue;
                }
                if ($push->send($token, $title, $body, $data)) {
                    $countPushes++;
                    $this->info(sprintf('Pushed to %s (ID %d) for loan %s', $g->name, $g->id, $loan->qard_id_string));
                }
            }
        }

        $this->info(sprintf('Completed. Loans with pending guarantors: %d, Pushes sent: %d', $countLoans, $countPushes));
        return self::SUCCESS;
    }
}
