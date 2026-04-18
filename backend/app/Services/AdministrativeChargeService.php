<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\AttendanceRecord;
use App\Models\TakafulContribution;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\CharityEntry;
use App\Models\AdministrativeCharge;
use App\Services\TakafulService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdministrativeChargeService
{
    /**
     * Process administrative charges for all eligible users.
     */
    public function processMonthlyCharges(): array
    {
        $amount = $this->getCharge('monthly_admin_charge', config('cooperative.admin_charges.amount', 300));
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
        $user->refresh();
        $due = $user->admin_charge_balance;
        if ($due <= 0) return true;

        // Check wallet balance
        if ($user->balance >= $due) {
            return DB::transaction(function () use ($user, $due, &$stats) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                if ($lockedUser->balance < $due) return false;

                // Deduct from wallet
                $lockedUser->decrement('balance', $due);
                $lockedUser->admin_charge_balance = 0;
                $lockedUser->save();

                // Create transaction record
                WalletTransaction::create([
                    'user_id' => $lockedUser->id,
                    'type' => 'debit',
                    'amount' => $due,
                    'reference' => 'ADMIN-CHG-' . $lockedUser->id . '-' . time(),
                    'source' => 'admin_charge',
                    'meta' => [
                        'description' => 'Administrative Charge Settlement',
                        'period' => Carbon::now()->format('Y-m'),
                        'full_settlement' => true
                    ]
                ]);

                if (isset($stats['auto_deducted'])) $stats['auto_deducted']++;
                if (isset($stats['total_deducted_amount'])) $stats['total_deducted_amount'] += $due;

                return true;
            });
        } else {
            // Partial deduction?
            if ($user->balance > 0) {
                return DB::transaction(function () use ($user, &$stats) {
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                    $deduction = $lockedUser->balance;
                    if ($deduction <= 0) return false;

                    $lockedUser->decrement('balance', $deduction);
                    $lockedUser->decrement('admin_charge_balance', $deduction);
                    $lockedUser->save();

                    WalletTransaction::create([
                        'user_id' => $lockedUser->id,
                        'type' => 'debit',
                        'amount' => $deduction,
                        'reference' => 'ADMIN-CHG-PART-' . $lockedUser->id . '-' . time(),
                        'source' => 'admin_charge',
                        'meta' => [
                            'description' => 'Partial Administrative Charge Settlement',
                            'amount_collected' => $deduction,
                            'remaining_balance' => $lockedUser->admin_charge_balance
                        ]
                    ]);

                    if (isset($stats['total_deducted_amount'])) $stats['total_deducted_amount'] += $deduction;
                    return true;
                });
            }

            if (isset($stats['failed_auto_deduct'])) $stats['failed_auto_deduct']++;
            return false;
        }
    }

    /**
     * Settle all types of outstanding dues for a user.
     * Includes admin charges, attendance fines, and potentially others.
     */
    public function settleAllDues(User $user): void
    {
        // 1. Settle Administrative Charges (if auto-deduct is enabled or it's a mandatory settlement)
        if ($user->admin_charge_balance > 0 && $user->balance > 0 && $user->admin_charge_auto_deduct) {
            $this->attemptDeduction($user);
        }

        // 2. Settle Attendance Fines (Mandatory)
        if ($user->outstanding_fines > 0 && $user->balance > 0) {
            $this->settleAttendanceFines($user);
        }

        // 3. Settle Pending Takaful Contributions
        if ($user->balance > 0) {
            $this->settlePendingTakaful($user);
        }

        // 4. Settle Pending Fines (from Contributions)
        if ($user->balance > 0) {
            $this->settlePendingFines($user);
        }
    }

    protected function settleAttendanceFines(User $user): void
    {
        $user->refresh();
        if ($user->outstanding_fines <= 0 || $user->balance <= 0) return;

        $deduction = min($user->balance, $user->outstanding_fines);

        DB::transaction(function () use ($user, $deduction) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $actualDeduction = min($lockedUser->balance, $lockedUser->outstanding_fines);
            if ($actualDeduction <= 0) return;

            $lockedUser->decrement('balance', $actualDeduction);
            $lockedUser->decrement('outstanding_fines', $actualDeduction);

            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $actualDeduction,
                'reference' => 'FINE_COLLECT_' . Str::random(8),
                'source' => 'attendance_fine_collection',
                'withdrawable' => true,
                'meta' => [
                    'description' => 'Automatic collection of accumulated attendance fines',
                    'amount_collected' => $actualDeduction
                ],
            ]);

            // Record in Charity Ledger
            CharityEntry::create([
                'user_id' => $lockedUser->id,
                'source' => 'Attendance Fine Collection',
                'amount' => $actualDeduction,
                'note' => 'Automatic collection of accumulated attendance fines',
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Update attendance records
            $pendingRecords = AttendanceRecord::where('user_id', $lockedUser->id)
                ->where('status', 'fine_pending')
                ->orderBy('created_at', 'asc')
                ->get();

            $remainingToMark = $actualDeduction;
            foreach ($pendingRecords as $record) {
                $fineAmount = (float)($record->meeting->fine_amount ?? $this->getCharge('attendance_fine', 500));
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
        });
    }

    protected function settlePendingTakaful(User $user): void
    {
        $pending = TakafulContribution::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('period', 'asc')
            ->get();

        foreach ($pending as $contribution) {
            if ($user->balance < $contribution->amount) break;

            // Re-use TakafulService if possible or implement here
            app(TakafulService::class)->payNow($user, $contribution->period, $contribution->amount);
            $user->refresh();
        }
    }

    protected function settlePendingFines(User $user): void
    {
        $fineSchemes = Scheme::whereIn('name', ['Lateness', 'Fine'])->pluck('id');

        $pendingFines = Contribution::where('user_id', $user->id)
            ->whereIn('scheme_id', $fineSchemes)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingFines as $fine) {
            if ($user->balance < $fine->amount) break;

            DB::transaction(function () use ($user, $fine) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                if ($lockedUser->balance < $fine->amount) return;

                $lockedUser->decrement('balance', $fine->amount);
                $fine->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'reference' => 'FINE_AUTO_' . Str::random(8)
                ]);

                WalletTransaction::create([
                    'user_id' => $lockedUser->id,
                    'type' => 'debit',
                    'amount' => $fine->amount,
                    'reference' => $fine->reference,
                    'source' => 'fine_collection',
                    'meta' => ['contribution_id' => $fine->id, 'description' => 'Auto-collection of pending fine']
                ]);
            });
            $user->refresh();
        }
    }

    /**
     * Get charge amount by slug from database or fallback to default.
     */
    public function getCharge(string $slug, float $default = 0, string $field = 'amount'): float
    {
        try {
            $charge = \App\Models\AdministrativeCharge::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            return $charge ? (float) ($charge->$field ?? 0) : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Calculate charge for a given slug and base amount.
     * Handles flat amount, percentage, and max amount cap.
     */
    public function calculateCharge(string $slug, float $baseAmount, ?float $defaultAmount = null, ?float $defaultPercentage = null, ?float $defaultMax = null): float
    {
        try {
            $charge = \App\Models\AdministrativeCharge::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$charge) {
                $amount = $defaultAmount ?? 0;
                $percentage = $defaultPercentage ?? 0;
                $max = $defaultMax ?? INF;

                $calculated = $amount + ($baseAmount * ($percentage / 100));
                return round(min($calculated, $max), 2);
            }

            $amount = (float) ($charge->amount ?? 0);
            $percentage = (float) ($charge->percentage ?? 0);
            $max = $charge->max_amount !== null ? (float) $charge->max_amount : INF;

            $calculated = $amount + ($baseAmount * ($percentage / 100));
            return round(min($calculated, $max), 2);
        } catch (\Exception $e) {
            // Fallback to simple calculation if everything fails
            $amount = $defaultAmount ?? 0;
            return round($amount, 2);
        }
    }
}
