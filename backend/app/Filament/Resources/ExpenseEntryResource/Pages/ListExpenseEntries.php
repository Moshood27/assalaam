<?php

namespace App\Filament\Resources\ExpenseEntryResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ExpenseEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListExpenseEntries extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ExpenseEntryResource::class;

    public function getSubheading(): ?string
    {
        return 'Log and categorize all operational and miscellaneous expenses.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
        ];
    }
}
