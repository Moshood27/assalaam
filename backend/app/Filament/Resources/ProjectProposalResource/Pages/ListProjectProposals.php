<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectProposals extends ListRecords
{
    protected static string $resource = ProjectProposalResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and manage new project proposals for potential investment.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
