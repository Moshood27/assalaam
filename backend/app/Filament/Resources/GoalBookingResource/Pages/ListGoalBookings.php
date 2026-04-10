<?php

namespace App\Filament\Resources\GoalBookingResource\Pages;

use App\Filament\Resources\GoalBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoalBookings extends ListRecords
{
    protected static string $resource = GoalBookingResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage member bookings and commitments towards specific savings goals.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
