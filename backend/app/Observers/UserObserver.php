<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // If balance increased and there are outstanding fines
        if ($user->wasChanged('balance') && $user->balance > $user->getOriginal('balance') && $user->outstanding_fines > 0) {
            $this->processOutstandingFines($user);
        }
    }

    protected function processOutstandingFines(User $user): void
    {
        // We use a separate transaction to avoid recursion issues if possible,
        // but here we are already inside a potential transaction from the trigger.
        // We'll use a lock to be safe.

        $user->refresh(); // Get latest data

        if ($user->outstanding_fines <= 0 || $user->balance <= 0) {
            return;
        }

        $deduction = min($user->balance, $user->outstanding_fines);

        if ($deduction <= 0) return;

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

            // Try to mark pending records as paid
            $pendingRecords = AttendanceRecord::where('user_id', $lockedUser->id)
                ->where('status', 'fine_pending')
                ->orderBy('created_at', 'asc')
                ->get();

            $remainingToMark = $deduction;
            foreach ($pendingRecords as $record) {
                $fineAmount = (float)($record->meeting->fine_amount ?? config('cooperative.attendance.default_fine', 500));
                if ($remainingToMark >= $fineAmount) {
                    $record->update([
                        'status' => 'fine_paid',
                        'fine_paid_at' => now()
                    ]);
                    $remainingToMark -= $fineAmount;
                } else {
                    // Partially paid? We don't have a partial status, so we leave it as pending
                    // or maybe we should have a partial_paid status?
                    // For now, keep it simple.
                    break;
                }
            }
        });
    }
}
