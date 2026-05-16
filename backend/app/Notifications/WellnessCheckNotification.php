<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WellnessCheckNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Wellness Check - ' . config('app.name'))
            ->greeting('Salam ' . $notifiable->name . ',')
            ->line('We noticed you haven\'t been active on the platform for a while.')
            ->line('We just wanted to check in and ensure everything is fine.')
            ->line('If you are seeing this, please log in to your account to confirm your activity.')
            ->action('Log in to Account', config('app.frontend_url', config('app.url')))
            ->line('This check is part of our legacy and estate planning (Wasiyyah) policy to ensure your cooperative assets are well managed.')
            ->line('Thank you for being part of our cooperative.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Wellness Check',
            'message' => 'We noticed you haven\'t been active for a while. Just checking in!',
            'type' => 'wellness_check'
        ];
    }
}
