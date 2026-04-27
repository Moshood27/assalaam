<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class OwnerChatRelationManager extends RelationManager
{
    protected static string $relationship = 'owner';

    protected static ?string $title = 'Support Chat';

    public function render(): View
    {
        return view('filament.resources.user-resource.relation-managers.support-messages', [
            'user' => $this->getOwnerRecord()->owner,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table;
    }
}
