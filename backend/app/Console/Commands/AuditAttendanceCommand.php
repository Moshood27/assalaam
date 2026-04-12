<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditAttendanceCommand extends Command
{
    protected $signature = 'app:audit-attendance';
    protected $description = 'Audit completed meetings and charge fines for absent members';

    public function handle()
    {
        // Audit meetings that are completed but not yet audited
        $meetings = Meeting::where('status', 'completed')->get();

        if ($meetings->isEmpty()) {
            $this->info("No meetings to audit.");
            return;
        }

        foreach ($meetings as $meeting) {
            $this->info("Auditing meeting: {$meeting->name} (ID: {$meeting->id})");

            // Define who should have attended (non-admins)
            $query = User::where('is_admin', false);
            if ($meeting->branch_id) {
                $query->where('branch_id', $meeting->branch_id);
            }

            $users = $query->get();

            foreach ($users as $user) {
                $record = AttendanceRecord::where('meeting_id', $meeting->id)
                    ->where('user_id', $user->id)
                    ->first();

                // If no record, or status is still 'absent', charge fine
                // Status could be 'present', 'apology_paid', 'fine_paid', or 'absent'
                if (!$record || $record->status === 'absent') {
                    $this->chargeFine($user, $meeting, $record);
                } else {
                    $this->line("Skipping User: {$user->name} (Status: {$record->status})");
                }
            }

            $meeting->update(['status' => 'audited']);
            $this->info("Meeting {$meeting->name} audited successfully.");
        }
    }

    private function chargeFine(User $user, Meeting $meeting, $record)
    {
        $amount = (float) $meeting->fine_amount;
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $meeting, $amount, $record) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // Check if user has enough balance
            if ((float)$lockedUser->balance >= $amount) {
                // Deduct from balance
                $lockedUser->decrement('balance', $amount);

                $reference = 'FINE_' . $meeting->id . '_' . Str::random(8);

                WalletTransaction::create([
                    'user_id' => $lockedUser->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'reference' => $reference,
                    'source' => 'attendance_fine',
                    'withdrawable' => true,
                    'meta' => [
                        'meeting_id' => $meeting->id,
                        'meeting_name' => $meeting->name,
                    ],
                ]);

                $status = 'fine_paid';
                $paidAt = now();
                $this->line("Charged fine of {$amount} to User: {$user->name} (ID: {$user->id})");
            } else {
                // Not enough balance, add to outstanding fines
                $lockedUser->increment('outstanding_fines', $amount);
                $status = 'fine_pending';
                $paidAt = null;
                $this->warn("Insufficient balance for User: {$user->name}. Added {$amount} to outstanding fines.");
            }

            if ($record) {
                $record->update([
                    'status' => $status,
                    'fine_paid_at' => $paidAt,
                ]);
            } else {
                AttendanceRecord::create([
                    'user_id' => $lockedUser->id,
                    'meeting_id' => $meeting->id,
                    'status' => $status,
                    'fine_paid_at' => $paidAt,
                ]);
            }
        });
    }
}
