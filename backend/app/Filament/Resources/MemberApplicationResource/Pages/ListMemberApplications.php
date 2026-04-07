<?php

namespace App\Filament\Resources\MemberApplicationResource\Pages;

use App\Filament\Resources\MemberApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberApplications extends ListRecords
{
    protected static string $resource = MemberApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No manual creation usually, handled by public API
        ];
    }
}
