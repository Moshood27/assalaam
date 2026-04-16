<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ProjectProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectProposals extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ProjectProposalResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and manage new project proposals for potential investment.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
