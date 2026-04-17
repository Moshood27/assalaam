<?php

namespace App\Channels;

use App\Services\PushService;
use Illuminate\Notifications\Notification;

class PushChannel
{
    public function __construct(protected PushService $push)
    {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toPush')) {
            return;
        }

        $message = $notification->toPush($notifiable);

        $token = $notifiable->fcm_token ?: ($notifiable->device_token ?? null);

        if (!$token) {
            return;
        }

        $this->push->send(
            $token,
            $message['title'],
            $message['body'],
            $message['data'] ?? [],
            $notifiable
        );
    }
}
