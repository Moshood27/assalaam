<?php

namespace App\Filament\Resources\UserBadgeResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\UserBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserBadges extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = UserBadgeResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage badges and recognition awards for system users.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
