<?php

namespace App\Filament\Resources\ChatRoomResource\Widgets;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChatStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Rooms', ChatRoom::count()),
            Stat::make('Total Messages', ChatMessage::count()),
            Stat::make('Active Rooms (24h)', ChatRoom::whereHas('messages', function ($query) {
                $query->where('created_at', '>=', now()->subDay());
            })->count()),
        ];
    }
}
