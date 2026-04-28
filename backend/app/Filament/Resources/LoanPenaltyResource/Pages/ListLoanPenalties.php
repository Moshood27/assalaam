<?php

namespace App\Filament\Resources\LoanPenaltyResource\Pages;

use App\Filament\Resources\LoanPenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanPenalties extends ListRecords
{
    protected static string $resource = LoanPenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action as it's a report/automated
        ];
    }
}
