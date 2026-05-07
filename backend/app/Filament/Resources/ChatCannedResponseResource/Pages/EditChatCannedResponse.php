<?php

namespace App\Filament\Resources\ChatCannedResponseResource\Pages;

use App\Filament\Resources\ChatCannedResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChatCannedResponse extends EditRecord
{
    protected static string $resource = ChatCannedResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
