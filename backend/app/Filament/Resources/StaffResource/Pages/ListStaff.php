<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('nominate_staff')
                ->label('Nominate Staff')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('user_id')
                        ->label('Select User')
                        ->options(\App\Models\User::whereDoesntHave('roles', fn($q) => $q->where('name', 'Staff'))->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $user = \App\Models\User::find($data['user_id']);
                    $user->assignRole('Staff');

                    \Filament\Notifications\Notification::make()
                        ->title($user->name . ' is now a Staff member')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('create_user')
                ->label('New User Account')
                ->url(\App\Filament\Resources\UserResource::getUrl('create'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
