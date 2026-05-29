<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'User Growth (30d)';

    protected function getData(): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(29);

        $rows = DB::table('users')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')
            ->pluck('c', 'd');

        $labels = [];
        $series = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $d = $date->toDateString();
            $labels[] = $d;
            $series[] = (int) ($rows[$d] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $series,
                    'borderColor' => '#10b981', // blue-500
                    'backgroundColor' => 'rgba(16,185,129,0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
