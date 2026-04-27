<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class SupportMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'supportMessages';

    protected static ?string $title = 'Support Chat';

    public function render(): View
    {
        return view('filament.resources.user-resource.relation-managers.support-messages', [
            'user' => $this->getOwnerRecord(),
        ]);
    }

    public function table(Table $table): Table
    {
        // We are overriding the render method, so this table won't be used
        // but Filament requires it to be defined.
        return $table;
    }
}
