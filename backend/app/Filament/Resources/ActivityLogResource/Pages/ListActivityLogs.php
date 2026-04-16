<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Traits\HasWipeAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ActivityLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Audit trail of all system activities and administrative actions.';
    }
    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
        ];
    }
}
