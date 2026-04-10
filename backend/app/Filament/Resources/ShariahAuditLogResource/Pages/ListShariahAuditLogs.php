<?php

namespace App\Filament\Resources\ShariahAuditLogResource\Pages;

use App\Filament\Resources\ShariahAuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListShariahAuditLogs extends ListRecords
{
    protected static string $resource = ShariahAuditLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Specialized audit trail for Shariah compliance and board reviews.';
    }
}
