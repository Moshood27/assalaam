<?php

namespace App\Filament\Resources\TakafulContributionResource\Pages;

use App\Filament\Resources\TakafulContributionResource;
use Filament\Resources\Pages\ListRecords;

class ListTakafulContributions extends ListRecords
{
    protected static string $resource = TakafulContributionResource::class;

    public function getSubheading(): ?string
    {
        return 'Monitor contributions towards Takaful (Mutual Insurance) funds.';
    }
}
