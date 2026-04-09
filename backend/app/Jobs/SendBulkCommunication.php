<?php

namespace App\Jobs;

use App\Mail\BulkCommunication;
use App\Models\User;
use App\Services\PushService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $count = 0;
        User::where('branch_id', $this->branchId)->chunk(200, function ($users) use (&$count) {
            foreach ($users as $user) {
                // Determine channels for this user: intersection of job's channels and user preferences
                $userChannels = array_filter($this->channels, function($ch) use ($user) {
                    if ($ch === 'sms') return (bool) ($user->notify_sms ?? true);
                    if ($ch === 'mail') return (bool) ($user->notify_email ?? true);
                    if ($ch === 'push') return (bool) ($user->notify_push ?? true);
                    return true; // database, etc
                });

                if (!empty($userChannels)) {
                    $user->notifyMember($this->title, $this->message, [], array_values($userChannels));
                    $count++;
                }
            }
        });

        Log::info("Bulk communication Job finished for branch {$this->branchId}. Total users notified: $count.");
    }
}
