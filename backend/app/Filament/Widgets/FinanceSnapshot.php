<?php

namespace App\Filament\Widgets;

use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\TakafulPoolEntry;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\GoldSilverPriceService;
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
            ->whereIn('status', ['active', 'defaulted'])
            ->get()
            ->sum(function ($q) {
                return (float) $q->principal_amount - (float) $q->paid_amount;
            });

        $totalLiability = (float) User::sum('balance');

        $poolBalance = TakafulPoolEntry::balance();

        $pendingWithdrawals = WithdrawalRequest::where('status', 'pending')->count();
        $pendingAmount = (float) WithdrawalRequest::where('status', 'pending')->sum('amount');

        $overdueCount = QardHasan::whereIn('status', ['active', 'defaulted'])
            ->get()
            ->filter(fn($q) => $q->getOverdueDays() > 0)
            ->count();

        $totalGold = (float) User::sum('gold_balance');
        $goldPriceData = (new GoldSilverPriceService())->getGoldPriceData();
        $goldPrice = $goldPriceData['sell_price_ngn'] ?? 0;
        $goldValue = $totalGold * $goldPrice;

        return [
            Card::make('Gold Reserves', number_format($totalGold, 4) . ' g')
                ->description('â‚¦' . number_format($goldValue, 2) . ' @ sell price')
                ->color('warning')
                ->icon('heroicon-o-archive-box'),
            Card::make('Active Loans Portfolio', 'â‚¦' . number_format((float) $activePortfolio, 2))
                ->description('Outstanding principal (Active)')
                ->color('warning'),
            Card::make('Total Collections (MTD)', 'â‚¦' . number_format((float) $mtd, 2))
                ->description('Successful contributions this month')
                ->color('success'),
            Card::make('Member Wallets (Liability)', 'â‚¦' . number_format($totalLiability, 2))
                ->description('Total liquidity owed to members')
                ->color('danger'),
            Card::make('Takaful Pool', 'â‚¦' . number_format($poolBalance, 2))
                ->description('Funds available for loan settlements')
                ->color('info'),
            Card::make('Pending Withdrawals', $pendingWithdrawals)
                ->description('â‚¦' . number_format($pendingAmount, 2) . ' awaiting processing')
                ->color($pendingWithdrawals > 0 ? 'danger' : 'gray'),
            Card::make('Overdue Loans', $overdueCount)
                ->description('Members at risk of default')
                ->color($overdueCount > 0 ? 'danger' : 'gray'),
        ];
    }
}
