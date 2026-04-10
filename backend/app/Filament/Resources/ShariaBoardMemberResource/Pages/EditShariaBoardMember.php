<?php

namespace App\Filament\Resources\ShariaBoardMemberResource\Pages;

use App\Filament\Resources\ShariaBoardMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShariaBoardMember extends EditRecord
{
    protected static string $resource = ShariaBoardMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
