<?php

namespace App\Filament\Resources\ChatRoomResource\Pages;

use App\Filament\Resources\ChatRoomResource;
use App\Models\User;
use App\Services\ChatService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChatRoom extends EditRecord
{
    protected static string $resource = ChatRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        if (isset($record->metadata['assigned_staff_id'])) {
            $staff = User::find($record->metadata['assigned_staff_id']);
            if ($staff) {
                app(ChatService::class)->assignStaff($record, $staff);
            }
        }
    }
}
