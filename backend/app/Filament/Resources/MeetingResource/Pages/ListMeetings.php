<?php

namespace App\Filament\Resources\MeetingResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\MeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetings extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
