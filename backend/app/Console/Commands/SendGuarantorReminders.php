<?php

namespace App\Console\Commands;

use App\Models\QardHasan;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendGuarantorReminders extends Command
{
    protected $signature = 'loans:remind-guarantors {--dry-run : Output targets without sending push notifications}';

    protected $description = 'Send push reminders to guarantors with pending decisions for all pending loan requests';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $countLoans = 0;
        $countPushes = 0;
        $countEscalated = 0;

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
            $body = 'Please review loan '.($loan->qard_id_string).' for '.($loan->user?->full_name ?? 'member').'. Accept or Decline in the app.';
            $data = [
                'type' => 'guarantor_reminder',
                'loan_id' => $loan->id,
                'qard_id_string' => $loan->qard_id_string,
            ];

            foreach ($pending as $g) {
                $token = $g->fcm_token ?: ($g->device_token ?? null);
                if ($dry) {
                    $this->info(sprintf('[DRY] Would push to %s (ID %d) for loan %s', $g->full_name, $g->id, $loan->qard_id_string));
                } else {
                    if ($push->send($token, $title, $body, $data)) {
                        $countPushes++;
                        $this->info(sprintf('Pushed to %s (ID %d) for loan %s', $g->full_name, $g->id, $loan->qard_id_string));
                    }

                    // Track nudges on pivot
                    DB::table('qard_hasan_guarantors')
                        ->where('qard_hasan_id', $loan->id)
                        ->where('guarantor_id', $g->id)
                        ->update([
                            'nudge_count' => DB::raw('COALESCE(nudge_count,0)+1'),
                            'last_nudged_at' => now(),
                        ]);
                }
            }

            // Auto-escalate if pending for more than 48 hours without prior escalation
            $threshold = Carbon::now()->subHours(48);
            $toEscalate = DB::table('qard_hasan_guarantors')
                ->where('qard_hasan_id', $loan->id)
                ->where('status', 'pending')
                ->whereNull('escalated_at')
                ->where('created_at', '<=', $threshold)
                ->count();

            if ($toEscalate > 0 && ! $dry) {
                $affected = DB::table('qard_hasan_guarantors')
                    ->where('qard_hasan_id', $loan->id)
                    ->where('status', 'pending')
                    ->whereNull('escalated_at')
                    ->where('created_at', '<=', $threshold)
                    ->update(['escalated_at' => now()]);
                $countEscalated += (int) $affected;

                // Notify authorized admins
                try {
                    $loan->user?->getAuthorizedAdmins()->each(function ($a) use ($loan, $affected, $push) {
                        $token = $a->fcm_token ?: ($a->device_token ?? null);
                        $push->send($token, 'Guarantor Escalation', sprintf('Loan %s for %s stalled. Pending guarantors escalated: %d', $loan->qard_id_string, $loan->user?->full_name ?? 'member', $affected), [
                            'type' => 'guarantor_escalation_auto',
                            'loan_id' => $loan->id,
                            'qard_id_string' => $loan->qard_id_string,
                        ]);
                    });
                } catch (\Throwable $e) {
                    // ignore
                }

                ShariahAudit::log(null, 'auto_escalate_guarantors', [
                    'loan_id' => $loan->id,
                    'qard_id_string' => $loan->qard_id_string,
                    'affected_rows' => (int) $affected,
                ]);
            }
        }

        $this->info(sprintf('Completed. Loans with pending guarantors: %d, Pushes sent: %d, Escalated: %d', $countLoans, $countPushes, $countEscalated));
        return self::SUCCESS;
    }
}
