<?php

namespace App\Filament\Resources\PendingApprovalResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\PendingApprovalResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingApprovals extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = PendingApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
        ];
    }
}
