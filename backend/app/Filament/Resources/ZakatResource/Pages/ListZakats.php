<?php

namespace App\Filament\Resources\ZakatResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ZakatResource;
use Filament\Resources\Pages\ListRecords;

class ListZakats extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ZakatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            // No header actions for now
        ];
    }
}
