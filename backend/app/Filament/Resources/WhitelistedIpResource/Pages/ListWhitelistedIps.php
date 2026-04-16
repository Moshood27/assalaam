<?php

namespace App\Filament\Resources\WhitelistedIpResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\WhitelistedIpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhitelistedIps extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = WhitelistedIpResource::class;

    public function getSubheading(): ?string
    {
        return 'Secure the admin panel by restricting access to specific IP addresses.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
