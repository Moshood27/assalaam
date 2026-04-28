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
                app(\App\Services\AttendanceService::class)->collectOutstandingFines($user);
            }
        }
    }
}
