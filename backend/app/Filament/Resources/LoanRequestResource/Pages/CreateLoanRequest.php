<?php

namespace App\Filament\Resources\LoanRequestResource\Pages;

use App\Filament\Resources\LoanRequestResource;
use App\Filament\Resources\QardHasanResource\Pages\CreateQardHasan;

class CreateLoanRequest extends CreateQardHasan
{
    protected static string $resource = LoanRequestResource::class;
}
