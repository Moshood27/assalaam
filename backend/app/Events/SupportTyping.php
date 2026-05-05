<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $senderType;
    public bool $isTyping;

    public function __construct(int $userId, string $senderType, bool $isTyping = true)
    {
        $this->userId = $userId;
        $this->senderType = $senderType;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('support.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'SupportTyping';
    }
}
