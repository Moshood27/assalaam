<?php

namespace App\Filament\Resources\MigrationDiscrepancyResource\Pages;

use App\Filament\Resources\MigrationDiscrepancyResource;
use Filament\Resources\Pages\ListRecords;

class ListMigrationDiscrepancies extends ListRecords
{
    protected static string $resource = MigrationDiscrepancyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Migration Discrepancy Reports';
    }

    public function getSubheading(): ?string
    {
        return 'Review and verify accounts where members have reported discrepancies in their migrated balances.';
    }
}
