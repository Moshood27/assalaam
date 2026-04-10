<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage third-party vendors and service providers.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
