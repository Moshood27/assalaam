<?php

namespace App\Filament\Widgets;

use App\Models\AgmSession;
use App\Models\AgmVote;
use App\Models\ProjectProposal;
use App\Models\ProjectProposalVote;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ShuraOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $navigationGroup = 'AGM & Voting';

    protected function getStats(): array
    {
        $activeAgms = AgmSession::where('status', 'open')->count();
        $activeProposals = ProjectProposal::where('status', 'voting')->count();

        $totalEligibleVoters = User::where('is_active', true)
            ->whereNull('deceased_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('loans')
                    ->whereColumn('loans.user_id', 'users.id')
                    ->where('loans.status', 'defaulted');
            })
            ->count();

        $totalAgmVotes = AgmVote::distinct('user_id')->count('user_id');
        $totalProposalVotes = ProjectProposalVote::distinct('user_id')->count('user_id');

        // Participation rate (estimate based on unique voters across all Shura activities)
        $uniqueVoters = DB::table('agm_votes')
            ->select('user_id')
            ->union(DB::table('project_proposal_votes')->select('user_id'))
            ->distinct()
            ->count();

        $participationRate = $totalEligibleVoters > 0
            ? round(($uniqueVoters / $totalEligibleVoters) * 100, 1)
            : 0;

        return [
            Stat::make('Active AGMs', $activeAgms)
                ->description('Currently open for voting')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('success'),
            Stat::make('Active Proposals', $activeProposals)
                ->description('Awaiting member Shura')
                ->icon('heroicon-o-light-bulb')
                ->color('info'),
            Stat::make('Member Participation', $participationRate . '%')
                ->description('Unique voters vs eligible members')
                ->icon('heroicon-o-users')
                ->color($participationRate > 50 ? 'success' : 'warning'),
        ];
    }
}
