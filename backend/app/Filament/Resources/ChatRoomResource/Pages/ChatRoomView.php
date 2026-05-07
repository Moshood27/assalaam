<?php

namespace App\Filament\Resources\ChatRoomResource\Pages;

use App\Filament\Resources\ChatRoomResource;
use Filament\Resources\Pages\Page;
use App\Models\ChatRoom;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ChatRoomView extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ChatRoomResource::class;

    protected static string $view = 'filament.resources.chat-room-resource.pages.chat-room-view';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return "Chat: " . ($this->record->name ?? 'Room');
    }
}
