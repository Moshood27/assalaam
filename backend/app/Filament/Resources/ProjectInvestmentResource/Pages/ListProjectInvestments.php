<?php

namespace App\Filament\Resources\ProjectInvestmentResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ProjectInvestmentResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectInvestments extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ProjectInvestmentResource::class;

    public function getSubheading(): ?string
    {
        return 'Track individual and collective investments into specific projects.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),,
        ];
    }
}
