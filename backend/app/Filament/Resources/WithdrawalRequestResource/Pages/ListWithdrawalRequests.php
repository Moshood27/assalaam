<?php

namespace App\Filament\Resources\WithdrawalRequestResource\Pages;

use App\Filament\Resources\WithdrawalRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawalRequests extends ListRecords
{
    protected static string $resource = WithdrawalRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and approve member requests to withdraw funds from wallets.';
    }
}
