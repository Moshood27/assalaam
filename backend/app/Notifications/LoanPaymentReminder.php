<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanPaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public float $amountToPay,
        public string $dueDateText
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email) && (bool) ($notifiable->notify_email ?? true)) {
            $channels[] = 'mail';
        }
        if ($notifiable->fcm_token || $notifiable->device_token) {
            $channels[] = PushChannel::class;
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Gentle Reminder: Qard Hasan Repayment (Barakah-Focused)')
            ->greeting('AssalÄmu â€˜alaykum ' . ($notifiable->full_name ?: $notifiable->name) . ',')
            ->line('We pray this message finds you in good health and Ä«mÄn.')
            ->line('This is a gentle reminder regarding your outstanding Qard Hasan repayment.')
            ->line('Amount to pay: â‚¦' . number_format($this->amountToPay, 2))
            ->line('Due Date: ' . $this->dueDateText)
            ->action('View My Account', url('/loans'))
            ->line('Fulfilling financial obligations is an important part of our faith. May AllÄh put barakah in your wealth and make repayment easy for you. Ä€mÄ«n.')
            ->line('JazÄkumullÄhu khayran for your commitment to our cooperative.');
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Qard Hasan Repayment Reminder',
            'body' => 'AssalÄmu â€˜alaykum, your Qard Hasan repayment of â‚¦' . number_format($this->amountToPay, 2) . ' is due (' . $this->dueDateText . '). JazÄkumullÄhu khayran.',
            'data' => [
                'type' => 'loan_reminder',
                'amount' => $this->amountToPay,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loan_reminder',
            'title' => 'Qard Hasan Repayment Reminder',
            'message' => 'AssalÄmu â€˜alaykum, your repayment of â‚¦' . number_format($this->amountToPay, 2) . ' is due (' . $this->dueDateText . ').',
            'amount' => $this->amountToPay,
            'due_date' => $this->dueDateText,
        ];
    }
}
