<?php

namespace App\Filament\Resources\ExpenseEntryResource\Pages;

use App\Filament\Resources\ExpenseEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListExpenseEntries extends ListRecords
{
    protected static string $resource = ExpenseEntryResource::class;
}
