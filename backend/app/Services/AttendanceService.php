<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\CharityEntry;
use App\Models\Meeting;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceService
{
    /**
     * Check if a user is late for a meeting.
     */
    public function isLate(Meeting $meeting, Carbon $attendedAt): bool
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        $startTime = Carbon::parse($meeting->date->format('Y-m-d') . ' ' . $meeting->start_time, $timezone);

        return $attendedAt->isAfter($startTime);
    }

    /**
     * Charge lateness fine to a user.
     */
    public function chargeLatenessFine(User $user, Meeting $meeting, float $amount = null): void
    {
        $amount = $amount ?: (float) config('cooperative.attendance.apology_fine', 100.00);
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $meeting, $amount) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ((float) $lockedUser->balance >= $amount) {
                // Deduct from balance
                $lockedUser->decrement('balance', $amount);

                $reference = 'LATE_' . $meeting->id . '_' . Str::random(8);

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
                        'type' => 'lateness_fine',
                    ],
                ]);

                // Record in Charity Ledger (Sadaqah fund)
                CharityEntry::create([
                    'user_id' => $lockedUser->id,
                    'source' => 'Lateness Fine',
                    'amount' => $amount,
                    'note' => "Lateness fine for meeting: {$meeting->name} (ID: {$meeting->id})",
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
            } else {
                // Not enough balance, add to outstanding fines
                $lockedUser->increment('outstanding_fines', $amount);
            }
        });
    }

    /**
     * Charge absence fine to a user.
     */
    public function chargeAbsenceFine(User $user, Meeting $meeting, AttendanceRecord $record = null): void
    {
        $amount = (float) $meeting->fine_amount;
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $meeting, $amount, $record) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $status = 'fine_pending';
            $paidAt = null;

            if ((float) $lockedUser->balance >= $amount) {
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
                        'type' => 'absence_fine',
                    ],
                ]);

                $status = 'fine_paid';
                $paidAt = now();

                // Record in Charity Ledger (Sadaqah fund)
                CharityEntry::create([
                    'user_id' => $lockedUser->id,
                    'source' => 'Attendance Fine',
                    'amount' => $amount,
                    'note' => "Fine for meeting: {$meeting->name} (ID: {$meeting->id})",
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
            } else {
                // Not enough balance, add to outstanding fines
                $lockedUser->increment('outstanding_fines', $amount);
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
