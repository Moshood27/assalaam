<?php

namespace App\Filament\Widgets;

use App\Models\Contribution;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class TotalCollectionsToday extends BaseWidget
{
    public function getHeading(): ?string
    {
        return 'Total Collections Today';
    }

    protected function getCards(): array
    {
        $today = Carbon::today();

        $sum = Contribution::query()
            ->whereDate('created_at', $today)
            ->where('status', 'success')
            ->sum('amount');

        return [
            Card::make('Total Collections Today', '₦' . number_format((float) $sum, 2))
                ->description('Successful contributions today')
                ->color('success'),
        ];
    }
}
