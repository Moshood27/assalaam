<?php

namespace App\Filament\Resources\ChatRoomResource\Pages;

use App\Filament\Resources\ChatRoomResource;
use App\Models\User;
use App\Services\ChatService;
use Filament\Resources\Pages\CreateRecord;

class CreateChatRoom extends CreateRecord
{
    protected static string $resource = ChatRoomResource::class;

    protected function afterCreate(): void
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
