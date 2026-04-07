<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
    public function handle(SmsService $smsService, PushService $pushService): void
    {
        $smsCount = 0;
        $pushCount = 0;

        User::where('branch_id', $this->branchId)->chunk(200, function ($users) use ($smsService, $pushService, &$smsCount, &$pushCount) {
            foreach ($users as $user) {
                if (in_array('sms', $this->channels)) {
                    $phone = $user->phone;
                    if ($phone && $smsService->send($phone, $this->message)) {
                        $smsCount++;
                    }
                }

                if (in_array('push', $this->channels)) {
                    $token = $user->fcm_token ?: $user->device_token;
                    if ($token && $pushService->send($token, $this->title, $this->message, [])) {
                        $pushCount++;
                    }
                }
            }
        });

        Log::info("Bulk communication Job finished for branch {$this->branchId}. SMS: $smsCount, Push: $pushCount.");

        // Optional: Could send a notification to the admin who triggered it if we wanted to be fancy.
    }
}
