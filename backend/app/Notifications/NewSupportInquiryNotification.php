<?php

namespace App\Notifications;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportInquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChatRoom $room,
        public User $member
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ((bool)($notifiable->notify_email ?? true) && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        if (!empty($notifiable->fcm_token) || !empty($notifiable->device_token)) {
            $channels[] = \App\Channels\PushChannel::class;
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/admin/chat-staff');
        return (new MailMessage)
            ->subject('New Support Inquiry: Action Required')
            ->greeting('AssalÄmu â€˜alaykum ' . $notifiable->name . ',')
            ->line("A new support inquiry has been started by {$this->member->name}.")
            ->line("Please assign an available staff member to handle this chat.")
            ->action('Assign Staff', $url)
            ->line('Thank you for your prompt attention!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Support Inquiry',
            'message' => "{$this->member->name} started a new support inquiry. Assign staff now.",
            'room_id' => $this->room->id,
            'member_id' => $this->member->id,
            'type' => 'new_support_inquiry',
        ];
    }

    /**
     * Get the push representation of the notification.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'New Support Inquiry',
            'body' => "{$this->member->name} needs assistance. Assign staff to chat.",
            'data' => [
                'type' => 'new_support_inquiry',
                'room_id' => $this->room->id,
            ],
        ];
    }
}
