<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?float $amount = null,
        public ?string $reference = null,
        public ?string $source = null
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email) && (bool) ($notifiable->notify_email ?? true)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('AssalÄmu â€˜alaykum '.$notifiable->name.',')
            ->line($this->message);

        if (!empty($this->amount)) {
            $mail->line('Amount: ₦'.number_format((float) $this->amount, 2));
        }
        if (!empty($this->reference)) {
            $mail->line('Reference: '.$this->reference);
        }
        if (!empty($this->source)) {
            $mail->line('Source: '.$this->source);
        }

        return $mail->line('Regards,')->line(config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'source' => $this->source,
        ];
    }
}
