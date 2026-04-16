<?php

namespace App\Filament\Resources\ShariaBoardMemberResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ShariaBoardMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShariaBoardMembers extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ShariaBoardMemberResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage profiles and details of Sharia Board members.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
