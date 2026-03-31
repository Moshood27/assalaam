<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $channel = 'sms', // sms | email | unknown
        public ?array $context = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'otp_sent',
            'title' => $this->title,
            'message' => $this->message,
            'channel' => $this->channel,
            'context' => $this->context,
        ];
    }
}
