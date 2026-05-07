<?php

namespace App\Filament\Widgets;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChatOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $chatService = app(ChatService::class);
        $analytics = $chatService->getChatAnalytics();

        return [
            Stat::make('Active Chat Rooms', $analytics['total_rooms'] ?? ChatRoom::count())
                ->icon('heroicon-o-chat-bubble-left-right')
                ->description('Conversations in progress')
                ->color('info'),
            Stat::make('Total Messages', $analytics['total_messages'] ?? ChatMessage::count())
                ->icon('heroicon-o-envelope')
                ->description('Total communication volume'),
            Stat::make('Unassigned Support', ChatRoom::where('type', 'support')
                ->where(function ($query) {
                    $query->whereNull('metadata->assigned_staff_id')
                          ->orWhere('metadata->assigned_staff_id', 'null')
                          ->orWhere('metadata->assigned_staff_id', '');
                })
                ->count())
                ->icon('heroicon-o-exclamation-circle')
                ->description('Needs attention (Amanah)')
                ->color('danger'),
            Stat::make('Avg Staff Response', $analytics['avg_response_time'] ?? 'N/A')
                ->icon('heroicon-o-clock')
                ->description('Maintaining Amanah')
                ->color('success'),
        ];
    }
}
