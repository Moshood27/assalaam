<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavingsGroup;
use App\Models\SavingsGroupMember;
use App\Models\Project;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsGroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $groups = SavingsGroup::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id)->where('status', 'active');
        })
        ->with(['project:id,name', 'creator:id,name'])
        ->withCount('activeMembers')
        ->get();

        return response()->json($groups);
    }

    public function discover(Request $request)
    {
        // For now, let's just return all active groups the user is not in
        $user = $request->user();

        $groups = SavingsGroup::whereDoesntHave('members', function($query) use ($user) {
            $query->where('user_id', $user->id)->where('status', 'active');
        })
        ->where('status', 'active')
        ->with(['project:id,name', 'creator:id,name'])
        ->withCount('activeMembers')
        ->get();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'purpose' => 'nullable|string',
            'monthly_contribution_amount' => 'required|numeric|min:100',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $user = $request->user();

        return DB::transaction(function() use ($validated, $user) {
            $group = SavingsGroup::create([
                'name' => $validated['name'],
                'purpose' => $validated['purpose'] ?? null,
                'monthly_contribution_amount' => $validated['monthly_contribution_amount'],
                'project_id' => $validated['project_id'] ?? null,
                'creator_id' => $user->id,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $group->members()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return response()->json([
                'message' => 'Savings group created successfully',
                'group' => $group->load(['project:id,name', 'creator:id,name']),
            ], 201);
        });
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $group = SavingsGroup::with(['project', 'creator:id,name', 'activeMembers.user:id,name'])
            ->withCount('activeMembers')
            ->findOrFail($id);

        // Check if user is a member
        $isMember = $group->members()->where('user_id', $user->id)->where('status', 'active')->exists();

        $stats = [
            'total_contributions' => (float) $group->totalContributions(),
            'my_contributions' => (float) $group->contributions()
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->sum('amount'),
        ];

        return response()->json([
            'group' => $group,
            'is_member' => $isMember,
            'stats' => $stats,
        ]);
    }

    public function join(Request $request, int $id)
    {
        $user = $request->user();
        $group = SavingsGroup::findOrFail($id);

        if ($group->status !== 'active') {
            return response()->json(['message' => 'This group is no longer active'], 422);
        }

        $existingMember = $group->members()->where('user_id', $user->id)->first();
        if ($existingMember) {
            if ($existingMember->status === 'active') {
                return response()->json(['message' => 'You are already a member of this group'], 422);
            }
            $existingMember->update(['status' => 'active', 'joined_at' => now()]);
        } else {
            $group->members()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Successfully joined the group']);
    }

    public function leave(Request $request, int $id)
    {
        $user = $request->user();
        $group = SavingsGroup::findOrFail($id);

        $member = $group->members()->where('user_id', $user->id)->where('status', 'active')->first();
        if (!$member) {
            return response()->json(['message' => 'You are not an active member of this group'], 422);
        }

        if ($group->creator_id === $user->id) {
            return response()->json(['message' => 'The creator cannot leave the group. You may dissolve it instead.'], 422);
        }

        $member->update(['status' => 'left']);

        return response()->json(['message' => 'Successfully left the group']);
    }
}
