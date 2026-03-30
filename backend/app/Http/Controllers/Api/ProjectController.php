<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvestment;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('active', true)
            ->orderBy('name')
            ->get(['id','name','description','target_amount','management_fee_percent','started_at','closed_at','active']);
        return response()->json($projects);
    }

    public function show(Request $request, int $id)
    {
        $project = Project::findOrFail($id);
        return response()->json($project);
    }

    public function myInvestments(Request $request, int $id)
    {
        $user = $request->user();
        $investments = ProjectInvestment::where('project_id', $id)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get(['id','amount','reference','created_at']);
        return response()->json([
            'project' => Project::findOrFail($id, ['id','name']),
            'investments' => $investments,
            'total_invested' => (float) $investments->sum('amount'),
        ]);
    }
}
