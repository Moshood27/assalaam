<?php

namespace App\Filament\Widgets;

use App\Models\AgmVote;
use App\Models\ProjectProposalVote;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ShuraParticipationChart extends ChartWidget
{
    protected static ?string $heading = 'Recent Voting Participation';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $agmData = AgmVote::select(DB::raw('DATE(created_at) as date'), DB::raw('count(DISTINCT user_id) as count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get()
            ->reverse();

        $proposalData = ProjectProposalVote::select(DB::raw('DATE(created_at) as date'), DB::raw('count(DISTINCT user_id) as count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get()
            ->reverse();

        return [
            'datasets' => [
                [
                    'label' => 'AGM Voters',
                    'data' => $agmData->pluck('count')->toArray(),
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Proposal Voters',
                    'data' => $proposalData->pluck('count')->toArray(),
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => $agmData->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
