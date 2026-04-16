<?php

namespace App\Filament\Resources\ShariahAuditLogResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ShariahAuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListShariahAuditLogs extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ShariahAuditLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Specialized audit trail for Shariah compliance and board reviews.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
        ];
    }
}
