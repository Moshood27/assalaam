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

        // Skip if user is in nursing mother grace period or has an approved/pending excuse
        if ($user->isInNursingMotherGracePeriod()) {
            return;
        }

        if ($record && in_array($record->status, ['excused', 'pending_excuse'])) {
            return;
        }

        $amount = !is_null($amount) ? (float)$amount : (float) ($meeting->apology_fine_amount ?? config('cooperative.attendance.apology_fine', 100.00));
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $meeting, $amount, $record) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // 1. Ensure record exists with lateness info
            if ($record) {
                $record->update([
                    'lateness_fine_amount' => $amount,
                    'lateness_fine_paid' => false,
                ]);
            } else {
                $record = AttendanceRecord::create([
                    'user_id' => $lockedUser->id,
                    'meeting_id' => $meeting->id,
                    'status' => 'present',
                    'attended_at' => now(),
                    'lateness_fine_amount' => $amount,
                    'lateness_fine_paid' => false,
                ]);
            }

            // 2. Increment outstanding fines (accumulation)
            $lockedUser->increment('outstanding_fines', $amount);

            // 3. Attempt to collect fines from balance
            $this->collectOutstandingFines($lockedUser);

            // 4. Refresh record to see if it was paid
            $record->refresh();
            $isPaid = $record->lateness_fine_paid;

            // 5. Notify user
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
        });
    }

    /**
     * Charge absence fine to a user.
     */
    public function chargeAbsenceFine(User $user, Meeting $meeting, AttendanceRecord $record = null): void
    {
        // Skip if user is in nursing mother grace period or has an approved/pending excuse
        if ($user->isInNursingMotherGracePeriod()) {
            return;
        }

        if ($record && in_array($record->status, ['excused', 'pending_excuse'])) {
            return;
        }

        $amount = (float) ($meeting->fine_amount ?? config('cooperative.attendance.default_fine', 500));
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $meeting, $amount, $record) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // 1. Ensure record exists with fine_pending status
            if ($record) {
                $record->update(['status' => 'fine_pending']);
            } else {
                $record = AttendanceRecord::create([
                    'user_id' => $lockedUser->id,
                    'meeting_id' => $meeting->id,
                    'status' => 'fine_pending',
                ]);
            }

            // 2. Increment outstanding fines (accumulation)
            $lockedUser->increment('outstanding_fines', $amount);

            // 3. Attempt to collect fines from balance
            $this->collectOutstandingFines($lockedUser);

            // 4. Refresh record to see if it was paid
            $record->refresh();
            $isPaid = ($record->status === 'fine_paid');

            // 5. Notify user
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
        });
    }

    /**
     * Collect outstanding fines from user balance.
     */
    public function collectOutstandingFines(User $user): float
    {
        $user->refresh();

        if ($user->outstanding_fines <= 0 || $user->balance <= 0) {
            return 0;
        }

        $deduction = min((float) $user->balance, (float) $user->outstanding_fines);

        if ($deduction <= 0) return 0;

        DB::transaction(function () use ($user, $deduction) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $lockedUser->decrement('balance', $deduction);
            $lockedUser->decrement('outstanding_fines', $deduction);

            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $deduction,
                'reference' => 'FINE_COLLECT_' . Str::random(8),
                'source' => 'attendance_fine_collection',
                'withdrawable' => true,
                'meta' => [
                    'description' => 'Automatic collection of accumulated attendance fines',
                    'amount_collected' => $deduction
                ],
            ]);

            // Record in Charity Ledger (Sadaqah fund)
            CharityEntry::create([
                'user_id' => $lockedUser->id,
                'source' => 'Attendance Fine Collection',
                'amount' => $deduction,
                'note' => 'Automatic collection of accumulated attendance fines',
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Try to mark pending records as paid
            $this->settleOutstandingFines($lockedUser, $deduction);
        });

        return $deduction;
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
