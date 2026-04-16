<?php

namespace App\Filament\Resources\WalletTransactionResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\WalletTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWalletTransactions extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = WalletTransactionResource::class;

    public function getSubheading(): ?string
    {
        return 'Detailed ledger of all digital wallet transactions and movements.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
