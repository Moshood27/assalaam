<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatRoomId;
    public $userId;
    public $userName;
    public $isTyping;

    /**
     * Create a new event instance.
     */
    public function __construct($chatRoomId, $userId, $userName, $isTyping)
    {
        $this->chatRoomId = $chatRoomId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.room.' . $this->chatRoomId),
        ];
    }

    public function broadcastAs()
    {
        return 'typing';
    }
}
