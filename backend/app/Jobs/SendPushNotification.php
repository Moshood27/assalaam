<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $token,
        public string $title,
        public string $message,
        public array $data = [],
        public ?int $userId = null
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = $this->userId ? User::find($this->userId) : null;
            app(PushService::class)->send($this->token, $this->title, $this->message, $this->data, $user);
        } catch (\Throwable $e) {
            Log::error('SendPushNotification job failed', ['error' => $e->getMessage()]);
        }
    }
}
