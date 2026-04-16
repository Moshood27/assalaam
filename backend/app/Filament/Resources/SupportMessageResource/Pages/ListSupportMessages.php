<?php

namespace App\Filament\Resources\SupportMessageResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\SupportMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportMessages extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = SupportMessageResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage and respond to member support queries and tickets.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            // Usually sent via user app or admin reply action
        ];
    }
}
