<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public array $message;

    public function __construct(SupportMessage $message)
    {
        $this->userId = (int) $message->user_id;
        $this->message = [
            'id' => (int) $message->id,
            'user_id' => (int) $message->user_id,
            'sender_type' => (string) $message->sender_type,
            'sender_id' => $message->sender_id ? (int) $message->sender_id : null,
            'body' => (string) $message->body,
            'type' => (string) ($message->type ?? 'text'),
            'attachment' => $message->attachment ? asset('storage/' . $message->attachment) : null,
            'attachment_name' => (string) $message->attachment_name,
            'created_at' => optional($message->created_at)->toISOString(),
            'read_at' => optional($message->read_at)->toISOString(),
        ];
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('support.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'SupportMessageSent';
    }
}
