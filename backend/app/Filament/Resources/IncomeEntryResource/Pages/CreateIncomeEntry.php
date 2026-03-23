<?php

namespace App\Filament\Resources\IncomeEntryResource\Pages;

use App\Filament\Resources\IncomeEntryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateIncomeEntry extends CreateRecord
{
    protected static string $resource = IncomeEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
