<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdministrativeChargeService
{
    /**
     * Process administrative charges for all eligible users.
     */
    public function processMonthlyCharges(): array
    {
        $amount = config('cooperative.admin_charges.amount', 300);
        $period = Carbon::now()->format('Y-m');

        $stats = [
            'total_users' => 0,
            'accrued' => 0,
            'auto_deducted' => 0,
            'failed_auto_deduct' => 0,
            'total_deducted_amount' => 0,
        ];

        // Process users who haven't been charged this month
        $users = User::whereNull('deceased_at')
            ->where(function ($query) use ($period) {
                $query->whereNull('last_admin_charge_at')
                      ->orWhere('last_admin_charge_at', '<', Carbon::now()->startOfMonth());
            })
            ->get();

        foreach ($users as $user) {
            $stats['total_users']++;

            DB::transaction(function () use ($user, $amount, $period, &$stats) {
                // 1. Accrue the charge
                $user->admin_charge_balance += $amount;
                $user->last_admin_charge_at = Carbon::now();
                $user->save();
                $stats['accrued']++;

                // 2. Auto-deduct if enabled
                if ($user->admin_charge_auto_deduct && $user->admin_charge_balance > 0) {
                    $this->attemptDeduction($user, $stats);
                }
            });
        }

        return $stats;
    }

    /**
     * Attempt to deduct the accumulated administrative charge from user wallet.
     */
    public function attemptDeduction(User $user, array &$stats = []): bool
    {
        $due = $user->admin_charge_balance;
        if ($due <= 0) return true;

        // Check wallet balance
        if ($user->balance >= $due) {
            return DB::transaction(function () use ($user, $due, &$stats) {
                // Deduct from wallet
                $user->balance -= $due;
                $user->admin_charge_balance = 0;
                $user->save();

                // Create transaction record
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'debit',
                    'amount' => $due,
                    'reference' => 'ADMIN-CHG-' . $user->id . '-' . time(),
                    'source' => 'admin_charge',
                    'meta' => [
                        'description' => 'Monthly Administrative Charge',
                        'period' => Carbon::now()->format('Y-m'),
                        'full_settlement' => true
                    ]
                ]);

                if (isset($stats['auto_deducted'])) $stats['auto_deducted']++;
                if (isset($stats['total_deducted_amount'])) $stats['total_deducted_amount'] += $due;

                return true;
            });
        } else {
            // Partial deduction or skip?
            // "implement the accumulation if member owns more than one month and deduct it from their wallet"
            // If they don't have enough, we can try to deduct what they have or just leave it for next time.
            // Usually it's better to deduct only if they have enough for the WHOLE due amount to keep it clean,
            // or deduct whatever they have.

            // Let's try to deduct at least some if possible?
            // Actually, if we want to "accumulate", it's fine to wait until they have enough.
            // But if they have 100 and owe 300, we could take 100.

            // For now, let's only deduct if they have enough to cover at least one full charge (300)
            // Or just the whole thing. Let's go with the whole thing for simplicity first.
            if (isset($stats['failed_auto_deduct'])) $stats['failed_auto_deduct']++;
            return false;
        }
    }
}
