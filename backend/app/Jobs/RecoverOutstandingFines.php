<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\AdministrativeChargeService;

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
        if (!$user || $user->balance <= 0) {
            return;
        }

        app(AdministrativeChargeService::class)->settleAllDues($user);
    }
}
