<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public array $data = [],
        public bool $useMail = true,
        public bool $useDatabase = true,
        public bool $usePush = true,
    ) {}

    /**
     * Determine channels dynamically based on flags and notifiable preferences.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        if ($this->useDatabase) {
            $channels[] = 'database';
        }
        if ($this->useMail && (bool)($notifiable->notify_email ?? true) && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        if ($this->usePush && (!empty($notifiable->fcm_token) || !empty($notifiable->device_token))) {
            $channels[] = \App\Channels\PushChannel::class;
        }
        return $channels;
    }

    /**
     * Push notification representation.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'data' => array_merge(['type' => 'general'], $this->data),
        ];
    }

    /**
     * Email representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('AssalÄmu â€˜alaykum '.$notifiable->name.',')
            ->line($this->message);

        // Add CTA if route provided
        $route = $this->data['route'] ?? null;
        if (is_string($route) && !empty($route)) {
            $appUrl = config('app.url');
            $url = str_starts_with($route, 'http') ? $route : rtrim($appUrl, '/').'/'.ltrim($route, '/');
            $mail->action('Open', $url);
        }

        $mail->line('Regards,')->line(config('app.name'));
        return $mail;
    }

    /**
     * Database representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'message' => $this->message,
        ], $this->data);
    }
}
