<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SystemHealthChart extends ChartWidget
{
    protected static ?string $heading = 'Payment Success vs Failure (14d)';

    protected function getData(): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(13);

        $rows = DB::table('contributions')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->select(DB::raw('DATE(created_at) as d'), 'status', DB::raw('COUNT(*) as c'))
            ->groupBy('d', 'status')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $d = $r->d;
            $status = strtolower((string) $r->status);
            if (! isset($map[$d])) {
                $map[$d] = ['success' => 0, 'failed' => 0];
            }
            if ($status === 'success') {
                $map[$d]['success'] += (int) $r->c;
            } elseif ($status === 'failed') {
                $map[$d]['failed'] += (int) $r->c;
            }
        }

        $labels = [];
        $succ = [];
        $fail = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $d = $date->toDateString();
            $labels[] = $d;
            $succ[] = (int) ($map[$d]['success'] ?? 0);
            $fail[] = (int) ($map[$d]['failed'] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Success',
                    'data' => $succ,
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Failed',
                    'data' => $fail,
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
