<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransparencyController extends Controller
{
    public function index(Request $request)
    {
        // Aggregate invested amounts per project
        $investedByProject = ProjectInvestment::query()
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        // Load all projects referenced by investments or active ones for visibility
        $projectIds = $investedByProject->keys()->all();
        $projects = Project::query()
            ->when(!empty($projectIds), function ($q) use ($projectIds) {
                $q->whereIn('id', $projectIds);
            }, function ($q) {
                // If none invested yet, still show active projects
                $q->where('active', true);
            })
            ->orderBy('name')
            ->get([
                'id','name','description','active','started_at','closed_at','report_url','media_urls'
            ]);

        $entries = [];
        $projectsTotal = 0.0;
        foreach ($projects as $p) {
            $amt = (float) ($investedByProject[$p->id] ?? 0.0);
            $projectsTotal += $amt;
            $entries[] = [
                'type' => 'project',
                'project_id' => $p->id,
                'name' => $p->name,
                'status' => $p->active ? 'Active' : 'Pending',
                'amount' => $amt,
                'attachments' => [
                    'report_url' => $p->report_url,
                    'media_urls' => $this->normalizeMediaUrls($p->media_urls),
                ],
            ];
        }

        // Cash = sum of all user wallet balances
        $cash = (float) User::query()->sum('balance');
        $totalAssets = $projectsTotal + $cash;

        // Compute percentages
        foreach ($entries as &$e) {
            $e['percent'] = $totalAssets > 0 ? round(($e['amount'] / $totalAssets) * 100, 2) : 0.0;
        }
        unset($e);

        $cashRow = [
            'type' => 'cash',
            'name' => 'Cash (Liquid)',
            'status' => 'Liquid',
            'amount' => $cash,
            'percent' => $totalAssets > 0 ? round(($cash / $totalAssets) * 100, 2) : 0.0,
        ];

        return response()->json([
            'total_assets' => round($totalAssets, 2),
            'projects_total' => round($projectsTotal, 2),
            'cash_total' => round($cash, 2),
            'breakdown' => array_values($entries),
            'cash' => $cashRow,
            'as_of' => now()->toISOString(),
        ]);
    }

    private function normalizeMediaUrls($media)
    {
        // Accept either an array of strings or array of {url: string}
        if (empty($media) || !is_array($media)) return [];
        $out = [];
        foreach ($media as $m) {
            if (is_string($m)) {
                $out[] = $m;
            } elseif (is_array($m) && isset($m['url'])) {
                $out[] = $m['url'];
            }
        }
        return $out;
    }
}
