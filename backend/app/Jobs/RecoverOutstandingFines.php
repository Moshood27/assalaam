<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\AttendanceRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecoverOutstandingFines implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $userId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user || (float)$user->outstanding_fines <= 0 || (float)$user->balance <= 0) {
            return;
        }

        DB::transaction(function () use ($user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $deduction = min((float)$lockedUser->balance, (float)$lockedUser->outstanding_fines);
            if ($deduction <= 0) return;

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
