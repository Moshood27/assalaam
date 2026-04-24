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

        $gracePeriod = $meeting->grace_period_minutes ?? (int) config('cooperative.attendance.grace_period_minutes', 0);
        $latenessStartTime = $startTime->copy()->addMinutes($gracePeriod);

        return $attendedAt->isAfter($latenessStartTime);
    }

    /**
     * Charge lateness fine to a user.
     */
    public function chargeLatenessFine(User $user, Meeting $meeting, float $amount = null): void
    {
        $record = AttendanceRecord::where('user_id', $user->id)
            ->where('meeting_id', $meeting->id)
            ->first();

        // If fine already paid, skip
        if ($record && $record->lateness_fine_paid) {
            return;
        }

        // Skip if user is in pregnancy grace period or has an approved/pending excuse
        if ($user->isInPregnancyGracePeriod()) {
            return;
        }

        if ($record && in_array($record->status, ['excused', 'pending_excuse'])) {
            return;
        }

        $amount = !is_null($amount) ? $amount : (float) ($meeting->apology_fine_amount ?? config('cooperative.attendance.apology_fine', 100.00));
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $meeting, $amount, $record) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $isPaid = false;
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

                $isPaid = true;
            } else {
                // Not enough balance, add to outstanding fines
                $lockedUser->increment('outstanding_fines', $amount);
            }

            // Notify user about lateness fine
            $lockedUser->notifyMember(
                "⚠️ Lateness Fine: {$meeting->name}",
                $isPaid
                    ? "A lateness fine of " . number_format($amount, 2) . " has been deducted from your balance for meeting: {$meeting->name}."
                    : "A lateness fine of " . number_format($amount, 2) . " has been added to your outstanding fines for meeting: {$meeting->name}. Please settle it as soon as possible.",
                [
                    'type' => 'lateness_fine',
                    'meeting_id' => (string) $meeting->id,
                    'amount' => (string) $amount,
                    'is_paid' => $isPaid ? 'true' : 'false'
                ]
            );

            // Update or create record with lateness info
            if ($record) {
                $record->update([
                    'lateness_fine_paid' => $isPaid,
                    'lateness_fine_amount' => $amount,
                ]);
            } else {
                AttendanceRecord::create([
                    'user_id' => $user->id,
                    'meeting_id' => $meeting->id,
                    'status' => 'present',
                    'attended_at' => now(),
                    'lateness_fine_paid' => $isPaid,
                    'lateness_fine_amount' => $amount,
                ]);
            }
        });
    }

    /**
     * Charge absence fine to a user.
     */
    public function chargeAbsenceFine(User $user, Meeting $meeting, AttendanceRecord $record = null): void
    {
        // Skip if user is in pregnancy grace period or has an approved/pending excuse
        if ($user->isInPregnancyGracePeriod()) {
            return;
        }

        if ($record && in_array($record->status, ['excused', 'pending_excuse'])) {
            return;
        }

        $amount = (float) ($meeting->fine_amount ?? config('cooperative.attendance.default_fine', 500));
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

            // Notify user about absence fine
            $isPaid = ($status === 'fine_paid');
            $lockedUser->notifyMember(
                "⚠️ Absence Fine: {$meeting->name}",
                $isPaid
                    ? "An absence fine of " . number_format($amount, 2) . " has been deducted from your balance for meeting: {$meeting->name}."
                    : "An absence fine of " . number_format($amount, 2) . " has been added to your outstanding fines for meeting: {$meeting->name}. Please settle it as soon as possible.",
                [
                    'type' => 'absence_fine',
                    'meeting_id' => (string) $meeting->id,
                    'amount' => (string) $amount,
                    'is_paid' => $isPaid ? 'true' : 'false'
                ]
            );

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

    /**
     * Settle outstanding fines by marking attendance records as paid.
     */
    public function settleOutstandingFines(User $user, float $amount): void
    {
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $amount) {
            // Try to mark pending records as paid (Absence Fines)
            $pendingRecords = AttendanceRecord::where('user_id', $user->id)
                ->where('status', 'fine_pending')
                ->orderBy('created_at', 'asc')
                ->get();

            $remainingToMark = $amount;
            foreach ($pendingRecords as $record) {
                $fineAmount = (float)($record->meeting->fine_amount ?? config('cooperative.attendance.default_fine', 500));
                if ($remainingToMark >= $fineAmount) {
                    $record->update([
                        'status' => 'fine_paid',
                        'fine_paid_at' => now()
                    ]);
                    $remainingToMark -= $fineAmount;
                } else {
                    break;
                }
            }

            // Try to mark lateness fines as paid
            if ($remainingToMark > 0) {
                $lateRecords = AttendanceRecord::where('user_id', $user->id)
                    ->where('lateness_fine_paid', false)
                    ->where('lateness_fine_amount', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($lateRecords as $record) {
                    $lateFineAmount = (float) $record->lateness_fine_amount;
                    if ($remainingToMark >= $lateFineAmount) {
                        $record->update([
                            'lateness_fine_paid' => true,
                        ]);
                        $remainingToMark -= $lateFineAmount;
                    } else {
                        break;
                    }
                }
            }
        });
    }

    /**
     * Waive all outstanding fines for a user.
     */
    public function waiveAllFines(User $user): void
    {
        DB::transaction(function () use ($user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $lockedUser->update(['outstanding_fines' => 0]);

            AttendanceRecord::where('user_id', $user->id)
                ->where('status', 'fine_pending')
                ->update([
                    'status' => 'fine_paid', // Mark as paid to remove from pending
                    'fine_paid_at' => now(),
                ]);

            AttendanceRecord::where('user_id', $user->id)
                ->where('lateness_fine_paid', false)
                ->update([
                    'lateness_fine_paid' => true,
                ]);
        });
    }

    /**
     * Wipe ALL outstanding fines from the entire system.
     */
    public function wipeAllSystemFines(): void
    {
        DB::transaction(function () {
            // Reset all user outstanding fines
            User::query()->update(['outstanding_fines' => 0]);

            // Mark all pending absence fines as paid/waived
            AttendanceRecord::where('status', 'fine_pending')
                ->update([
                    'status' => 'fine_paid',
                    'fine_paid_at' => now(),
                ]);

            // Mark all lateness fines as paid
            AttendanceRecord::where('lateness_fine_paid', false)
                ->where('lateness_fine_amount', '>', 0)
                ->update([
                    'lateness_fine_paid' => true,
                ]);
        });
    }
}
