<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Models\ProjectProfit;
use App\Models\ProjectProfitPayout;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('active', true)
            ->orderBy('name')
            ->get(['id','name','description','target_amount','management_fee_percent','started_at','closed_at','active', 'is_unit_based', 'unit_price', 'total_units', 'available_units']);
        return response()->json($projects);
    }

    public function show(Request $request, int $id)
    {
        $project = Project::with(['updates' => fn($q) => $q->latest()])->findOrFail($id);
        return response()->json($project);
    }

    public function myInvestments(Request $request, int $id)
    {
        $user = $request->user();
        $investments = ProjectInvestment::where('project_id', $id)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get(['id','amount','units','reference','created_at']);
        return response()->json([
            'project' => Project::findOrFail($id, ['id','name','is_unit_based','unit_price']),
            'investments' => $investments,
            'total_invested' => (float) $investments->sum('amount'),
            'total_units' => (int) $investments->sum('units'),
        ]);
    }

    public function profits(Request $request, int $id)
    {
        $user = $request->user();
        $project = Project::findOrFail($id, ['id','name','management_fee_percent']);

        // Fetch profits for this project
        $profits = ProjectProfit::where('project_id', $id)
            ->orderByDesc('created_at')
            ->get(['id','project_id','gross_profit','management_fee_percent','management_fee_amount','net_distributable','note','created_at']);

        $isUnitBased = (bool) ($project->is_unit_based ?? false);

        // Preload user total invested at each profit timestamp and total invested overall at that time
        $result = [];
        foreach ($profits as $p) {
            $cut = $p->created_at;
            $userTotals = ProjectInvestment::query()
                ->selectRaw('SUM(amount) as amount, SUM(units) as units')
                ->where('project_id', $id)
                ->where('user_id', $user->id)
                ->where('created_at', '<=', $cut)
                ->first();

            $allTotals = ProjectInvestment::query()
                ->selectRaw('SUM(amount) as amount, SUM(units) as units')
                ->where('project_id', $id)
                ->where('created_at', '<=', $cut)
                ->first();

            $userVal = $isUnitBased ? (float) ($userTotals->units ?? 0) : (float) ($userTotals->amount ?? 0);
            $allVal = $isUnitBased ? (float) ($allTotals->units ?? 0) : (float) ($allTotals->amount ?? 0);

            $net = (float) $p->net_distributable;
            $expected = 0.0;
            if ($allVal > 0 && $net > 0) {
                $expected = round(($userVal / $allVal) * $net, 2);
            }

            $myPayout = ProjectProfitPayout::where('project_profit_id', $p->id)
                ->where('user_id', $user->id)
                ->first(['id','amount','status','created_at','notified_at']);

            $result[] = [
                'id' => $p->id,
                'created_at' => $p->created_at,
                'gross_profit' => (float) $p->gross_profit,
                'management_fee_percent' => (float) $p->management_fee_percent,
                'management_fee_amount' => (float) $p->management_fee_amount,
                'net_distributable' => (float) $p->net_distributable,
                'note' => $p->note,
                'my_expected_share' => (float) $expected,
                'my_payout' => $myPayout ? [
                    'id' => $myPayout->id,
                    'amount' => (float) $myPayout->amount,
                    'status' => $myPayout->status,
                    'created_at' => $myPayout->created_at,
                    'notified_at' => $myPayout->notified_at,
                ] : null,
            ];
        }

        return response()->json([
            'project' => $project,
            'profits' => $result,
        ]);
    }
}
