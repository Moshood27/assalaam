<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\StoreOrder;
use App\Models\Product;

class StoreOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSales = StoreOrder::whereIn('status', ['paid', 'murabaha_active', 'completed'])->sum('total_amount');
        $pendingMurabaha = StoreOrder::where('status', 'murabaha_pending')->count();
        $outOfStock = Product::where('track_stock', true)->where('stock_quantity', '<=', 0)->count();

        return [
            Stat::make('Total Store Sales', '₦ ' . number_format($totalSales, 2))
                ->description('Combined cash and active financing')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Pending Murabaha', $pendingMurabaha)
                ->description('Applications awaiting approval')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
            Stat::make('Out of Stock', $outOfStock)
                ->description('Products needing restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outOfStock > 0 ? 'danger' : 'gray'),
        ];
    }
}
