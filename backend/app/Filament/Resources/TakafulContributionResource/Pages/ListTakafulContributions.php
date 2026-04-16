<?php

namespace App\Filament\Resources\TakafulContributionResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\TakafulContributionResource;
use Filament\Resources\Pages\ListRecords;

class ListTakafulContributions extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = TakafulContributionResource::class;

    public function getSubheading(): ?string
    {
        return 'Monitor contributions towards Takaful (Mutual Insurance) funds.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),,
        ];
    }
}
