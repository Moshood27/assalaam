<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncUserBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public string $schemeName;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $schemeName)
    {
        $this->userId = $userId;
        $this->schemeName = $schemeName;
        // Use a dedicated queue for balance syncing to avoid blocking high-priority jobs
        $this->onQueue('balances');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user) {
                return;
            }

            $user->syncSchemeBalance($this->schemeName);
        } catch (\Throwable $e) {
            Log::error('SyncUserBalance job failed', [
                'user_id' => $this->userId,
                'scheme' => $this->schemeName,
                'error' => $e->getMessage()
            ]);
        }
    }
}
