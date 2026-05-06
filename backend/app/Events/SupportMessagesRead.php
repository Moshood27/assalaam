<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessagesRead implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $readerType;

    public function __construct(int $userId, string $readerType)
    {
        $this->userId = $userId;
        $this->readerType = $readerType;
    }

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('support.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'SupportMessagesRead';
    }
}
