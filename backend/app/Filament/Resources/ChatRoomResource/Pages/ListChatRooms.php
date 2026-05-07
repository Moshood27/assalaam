<?php

namespace App\Filament\Resources\ChatRoomResource\Pages;

use App\Services\ChatService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class ListChatRooms extends ListRecords
{
    protected static string $resource = ChatRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('broadcast')
                ->label('Broadcast Announcement')
                ->icon('heroicon-o-megaphone')
                ->color('warning')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->required()
                        ->placeholder('Enter the announcement message...'),
                ])
                ->action(function (array $data, ChatService $chatService) {
                    $chatService->broadcastMessage(Auth::user(), $data['message']);

                    Notification::make()
                        ->title('Broadcast sent successfully')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ChatRoomResource\Widgets\ChatStatsWidget::class,
        ];
    }
}
