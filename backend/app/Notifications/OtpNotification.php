<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $channel = 'sms', // sms | email | push | all
        public ?array $context = null
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $requestedChannels = explode(',', $this->channel);

        if (in_array('push', $requestedChannels) || in_array('all', $requestedChannels)) {
            $channels[] = PushChannel::class;
        }

        if (in_array('sms', $requestedChannels) || in_array('all', $requestedChannels)) {
            $channels[] = SmsChannel::class;
        }

        if (in_array('email', $requestedChannels) || in_array('all', $requestedChannels)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name)
            ->line($this->message)
            ->line('If you did not request this, please ignore this message.');
    }

    public function toSms(object $notifiable): string
    {
        return $this->message;
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'data' => array_merge(['type' => 'otp'], $this->context ?? []),
        ];
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
