<?php

namespace App\Filament\Resources\UtilityTransactionResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\UtilityTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUtilityTransactions extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = UtilityTransactionResource::class;

    public function getSubheading(): ?string
    {
        return 'Track utility payments and bill settlements through the platform.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
