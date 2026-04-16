<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = VendorResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage third-party vendors and service providers.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
