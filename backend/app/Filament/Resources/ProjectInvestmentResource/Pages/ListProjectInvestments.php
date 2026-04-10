<?php

namespace App\Filament\Resources\ProjectInvestmentResource\Pages;

use App\Filament\Resources\ProjectInvestmentResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectInvestments extends ListRecords
{
    protected static string $resource = ProjectInvestmentResource::class;

    public function getSubheading(): ?string
    {
        return 'Track individual and collective investments into specific projects.';
    }
}
