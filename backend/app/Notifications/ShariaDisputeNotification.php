<?php

namespace App\Notifications;

use App\Models\ShariaDispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShariaDisputeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $dispute;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(ShariaDispute $dispute, string $type = 'created')
    {
        $this->dispute = $dispute;
        $this->type = $type; // created, updated
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $orderRef = $notifiable->id === $this->dispute->user_id ? 'your order' : 'order #' . $this->dispute->order?->reference;

        if ($this->type === 'created') {
            return (new MailMessage)
                ->subject('New Sharia Dispute (Tahkim) Raised')
                ->line('A new Sharia dispute has been raised for ' . $orderRef . '.')
                ->line('Reason: ' . $this->dispute->reason)
                ->action('View Dispute', url('/admin/sharia-disputes/' . $this->dispute->id . '/edit'))
                ->line('Please review the case and initiate mediation.');
        }

        return (new MailMessage)
            ->subject('Sharia Dispute (Tahkim) Status Update')
            ->line('There has been an update to the Sharia dispute for ' . $orderRef . '.')
            ->line('New Status: ' . strtoupper($this->dispute->status))
            ->line('Outcome: ' . ($this->dispute->outcome_details ?? 'Under review'))
            ->action('View Details', url('/sharia-board/history'))
            ->line('Thank you for your patience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'order_id' => $this->dispute->store_order_id,
            'type' => $this->type,
            'status' => $this->dispute->status,
            'reason' => $this->dispute->reason,
            'message' => $this->type === 'created'
                ? 'A new Sharia dispute has been raised.'
                : 'Your Sharia dispute status has been updated to ' . $this->dispute->status . '.',
        ];
    }
}
