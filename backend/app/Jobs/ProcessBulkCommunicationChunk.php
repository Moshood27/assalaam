<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBulkCommunicationChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $message,
        public array $channels
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $users = User::whereIn('id', $this->userIds)->get();

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
            }
        }
    }
}
