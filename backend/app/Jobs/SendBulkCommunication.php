<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SendBulkCommunication implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $branchId,
        public string $title,
        public string $message,
        public array $channels,
        public ?int $adminId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batchJobs = [];

        User::where('branch_id', $this->branchId)->chunk(500, function ($users) use (&$batchJobs) {
            $batchJobs[] = new ProcessBulkCommunicationChunk(
                $users->pluck('id')->toArray(),
                $this->title,
                $this->message,
                $this->channels
            );
        });

        if (!empty($batchJobs)) {
            Bus::batch($batchJobs)
                ->name("Bulk Communication - Branch {$this->branchId}")
                ->dispatch();
        }

        Log::info("Bulk communication batch dispatched for branch {$this->branchId}. Total chunks: " . count($batchJobs));
    }
}
