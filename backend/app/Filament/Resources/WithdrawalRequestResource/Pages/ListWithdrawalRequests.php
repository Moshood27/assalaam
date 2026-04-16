<?php

namespace App\Filament\Resources\WithdrawalRequestResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\WithdrawalRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawalRequests extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = WithdrawalRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and approve member requests to withdraw funds from wallets.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),,
        ];
    }
}
