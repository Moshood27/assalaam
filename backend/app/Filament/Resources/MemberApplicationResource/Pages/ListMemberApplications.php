<?php

namespace App\Filament\Resources\MemberApplicationResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\MemberApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberApplications extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = MemberApplicationResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and process new membership applications and registrations.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            // No manual creation usually, handled by public API
        ];
    }
}
