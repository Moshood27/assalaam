<?php

namespace App\Filament\Resources\ChatRoomResource\Widgets;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChatStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $chatService = app(ChatService::class);
        $analytics = $chatService->getChatAnalytics();

        return [
            Stat::make('Total Rooms', $analytics['total_rooms'] ?? ChatRoom::count()),
            Stat::make('Total Messages', $analytics['total_messages'] ?? ChatMessage::count()),
            Stat::make('Active Members', $analytics['active_members'] ?? 0),
            Stat::make('Avg Response Time', $analytics['avg_response_time'] ?? 'N/A')
                ->description('Staff responsiveness (Amanah)')
                ->color('success'),
            Stat::make('Unassigned Support', ChatRoom::where('type', 'support')->whereNull('metadata->assigned_staff_id')->count())
                ->color('danger')
                ->description('Needs attention (Amanah)'),
        ];
    }
}
