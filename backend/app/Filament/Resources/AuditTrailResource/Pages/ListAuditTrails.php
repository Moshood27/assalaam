<?php

namespace App\Filament\Resources\AuditTrailResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\AuditTrailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuditTrails extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = AuditTrailResource::class;

    public function getSubheading(): ?string
    {
        return 'General system audit trail for tracking model changes and events.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
        ];
    }
}
