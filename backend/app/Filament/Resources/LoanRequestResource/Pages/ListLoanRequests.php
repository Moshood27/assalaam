<?php

namespace App\Filament\Resources\LoanRequestResource\Pages;

use App\Filament\Resources\LoanRequestResource;
use App\Filament\Resources\QardHasanResource\Pages\ListQardHasans;

class ListLoanRequests extends ListQardHasans
{
    protected static string $resource = LoanRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and approve pending loan applications.';
    }
}
