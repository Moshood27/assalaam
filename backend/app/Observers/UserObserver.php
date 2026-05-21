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
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Trigger admin notification for real-time dashboard
        event(new \App\Events\NewMemberJoined($user));

        // Assign default role if none assigned
        try {
            if ($user->roles()->count() === 0) {
                $user->assignRole('member');
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Trigger real-time dashboard update if any balance changed
        // This ensures numbers stay fresh even if no notification message is sent
        if ($user->wasChanged([
            'balance', 'gold_balance', 'ordinary_savings', 'special_savings_balance',
            'shares_capital', 'takaful_balance', 'outstanding_fines'
        ])) {
            event(new \App\Events\UserAccountUpdated($user));
        }

        // If balance increased
        if ($user->wasChanged('balance') && $user->balance > $user->getOriginal('balance')) {
            // 1. Process outstanding fines
            if ($user->outstanding_fines > 0) {
                $this->processOutstandingFines($user);
            }
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

            // Record in Charity Ledger (Sadaqah fund)
            \App\Models\CharityEntry::create([
                'user_id' => $lockedUser->id,
                'source' => 'Attendance Fine Collection',
                'amount' => $deduction,
                'note' => 'Automatic collection of accumulated attendance fines',
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Try to mark pending records as paid (Absence Fines)
            app(\App\Services\AttendanceService::class)->settleOutstandingFines($lockedUser, $deduction);
        });
    }
}
