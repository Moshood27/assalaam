<?php

namespace App\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(protected SmsService $sms)
    {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $to = $notifiable->phone;

        if (!$to) {
            return;
        }

        $this->sms->send($to, $message);
    }
}
