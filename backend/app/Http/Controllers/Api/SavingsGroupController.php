<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavingsGroup;
use App\Models\SavingsGroupMember;
use App\Models\Project;
use App\Models\Scheme;
use App\Models\Contribution;
use App\Models\User;
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

    public function invitations(Request $request)
    {
        $user = $request->user();

        $groups = SavingsGroup::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id)->where('status', 'pending');
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

        // If project is unit-based, amount must be a multiple of unit price
        if (!empty($validated['project_id'])) {
            $project = Project::find($validated['project_id']);
            if ($project && $project->is_unit_based) {
                $unitPrice = (float) $project->unit_price;
                if ($unitPrice > 0) {
                    $amount = (float) $validated['monthly_contribution_amount'];
                    if (fmod($amount, $unitPrice) != 0) {
                        return response()->json([
                            'message' => "Monthly contribution must be a multiple of the project unit price (₦" . number_format($unitPrice, 2) . ")"
                        ], 422);
                    }
                }
            }
        }

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

            // Notify relevant admins about new savings group creation
            $user->getAuthorizedAdmins()->each(function ($admin) use ($group, $user) {
                $admin->notifyMember(
                    "New Savings Group Created",
                    "A new savings group '{$group->name}' has been created by {$user->name}.",
                    ['type' => 'new_savings_group', 'group_id' => $group->id]
                );
            });

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
        $isPending = $group->members()->where('user_id', $user->id)->where('status', 'pending')->exists();

        $stats = [
            'total_contributions' => (float) $group->totalContributions(),
            'my_contributions' => (float) $group->contributions()
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->sum('amount'),
        ];

        $recentContributions = $group->contributions()
            ->with('user:id,name')
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'group' => $group,
            'is_member' => $isMember,
            'is_pending' => $isPending,
            'stats' => $stats,
            'recent_contributions' => $recentContributions,
            'is_creator' => $group->creator_id === $user->id,
        ]);
    }

    public function projects()
    {
        $projects = Project::where('active', true)->orderBy('name')->get(['id', 'name', 'is_unit_based', 'unit_price']);
        return response()->json($projects);
    }

    public function getContributionData(Request $request, int $id)
    {
        $group = SavingsGroup::with('project')->findOrFail($id);
        $scheme = Scheme::where('name', 'Group Savings')->first();

        if (!$scheme) {
            return response()->json(['message' => 'Group Savings scheme not found'], 404);
        }

        return response()->json([
            'group' => $group,
            'scheme' => $scheme,
            'amount' => (float) $group->monthly_contribution_amount,
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

    public function invite(Request $request, int $id)
    {
        $user = $request->user();
        $group = SavingsGroup::findOrFail($id);

        if ($group->creator_id !== $user->id) {
            return response()->json(['message' => 'Only the creator can invite new members'], 403);
        }

        $validated = $request->validate([
            'identifier' => 'required|string', // phone or membership number
        ]);

        $recipient = User::where('phone', $validated['identifier'])
            ->orWhere('membership_number', $validated['identifier'])
            ->first();

        if (!$recipient) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $existingMember = $group->members()->where('user_id', $recipient->id)->first();
        if ($existingMember) {
            if ($existingMember->status === 'active') {
                return response()->json(['message' => 'User is already a member'], 422);
            }
            if ($existingMember->status === 'pending') {
                return response()->json(['message' => 'User already has a pending invitation'], 422);
            }
            $existingMember->update(['status' => 'pending']);
        } else {
            $group->members()->create([
                'user_id' => $recipient->id,
                'status' => 'pending',
            ]);
        }

        // Notify user
        try {
            $recipient->notify(new \App\Notifications\GeneralNotification(
                title: "Savings Group Invitation",
                message: "{$user->name} has invited you to join the savings group: {$group->name}",
                data: ['route' => "/savings-groups/{$group->id}"]
            ));
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'Invitation sent successfully']);
    }

    public function acceptInvitation(Request $request, int $id)
    {
        $user = $request->user();
        $group = SavingsGroup::findOrFail($id);

        $member = $group->members()->where('user_id', $user->id)->where('status', 'pending')->first();
        if (!$member) {
            return response()->json(['message' => 'No pending invitation found'], 404);
        }

        $member->update([
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Invitation accepted successfully']);
    }

    public function dissolve(Request $request, int $id)
    {
        $user = $request->user();
        $group = SavingsGroup::findOrFail($id);

        if ($group->creator_id !== $user->id) {
            return response()->json(['message' => 'Only the creator can dissolve the group'], 403);
        }

        if ($group->totalContributions() > 0) {
            return response()->json(['message' => 'Cannot dissolve a group with active contributions. Please contact support.'], 422);
        }

        $group->update(['status' => 'dissolved']);

        return response()->json(['message' => 'Group dissolved successfully']);
    }
}
