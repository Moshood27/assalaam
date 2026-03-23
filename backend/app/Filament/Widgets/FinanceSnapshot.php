<?php

namespace App\Filament\Widgets;

use App\Models\Contribution;
use App\Models\QardHasan;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class FinanceSnapshot extends BaseWidget
{
    public function getHeading(): ?string
    {
        return 'Finance Snapshot';
    }

    protected function getCards(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $mtd = Contribution::query()
            ->where('status', 'success')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $activePortfolio = QardHasan::query()
            ->where('status', 'active')
            ->get()
            ->sum(function ($q) {
                $remaining = (float) $q->principal_amount - (float) $q->paid_amount;
                return $remaining > 0 ? $remaining : 0;
            });

        return [
            Card::make('Active Loans Portfolio', '₦' . number_format((float) $activePortfolio, 2))
                ->description('Outstanding principal across active Qard Hasan loans')
                ->color('warning'),
            Card::make('Total Collections (MTD)', '₦' . number_format((float) $mtd, 2))
                ->description('Successful contributions this month')
                ->color('success'),
        ];
    }
}
