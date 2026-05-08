<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectProposal;
use App\Models\ProjectProposalVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectProposalController extends Controller
{
    public function index(Request $request)
    {
        $proposals = ProjectProposal::with('user:id,name')
            ->whereIn('sharia_status', ['compliant', 'pending_review']) // Only show compliant or pending ones
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($proposals);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isEligibleForShura()) {
            return response()->json(['message' => 'You are not eligible to submit proposals'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'target_amount' => 'nullable|numeric|min:0',
        ]);

        $proposal = ProjectProposal::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'target_amount' => $validated['target_amount'] ?? null,
            'status' => 'pending',
            'voting_type' => 'one_member_one_vote', // Default for new proposals
        ]);

        // Notify relevant admins/Sharia board
        $user->getAuthorizedAdmins()->each(function ($admin) use ($proposal, $user) {
            $admin->notifyMember(
                "New Project Proposal",
                "A new proposal '{$proposal->title}' has been submitted by {$user->name} for Sharia review."
            );
        });

        return response()->json([
            'message' => 'Proposal submitted successfully',
            'proposal' => $proposal
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $proposal = ProjectProposal::with(['user:id,name', 'comments.user:id,name'])->findOrFail($id);

        $results = ProjectProposalVote::query()
            ->select('choice', DB::raw('SUM(weight) as total_weight'))
            ->where('project_proposal_id', $id)
            ->groupBy('choice')
            ->get()
            ->pluck('total_weight', 'choice');

        // Ensure yes/no exist
        $results = [
            'yes' => (float) ($results['yes'] ?? 0),
            'no' => (float) ($results['no'] ?? 0),
        ];

        $isTie = ($results['yes'] > 0 && $results['yes'] === $results['no']);

        $myVote = ProjectProposalVote::where('project_proposal_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        // Participation metrics
        $totalEligible = \App\Models\User::query()
            ->where('is_defaulter', false)
            ->whereNull('deceased_at')
            ->count();
        $totalCast = ProjectProposalVote::query()
            ->where('project_proposal_id', $id)
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'proposal' => $proposal,
            'results' => $results,
            'is_tie' => $isTie,
            'my_vote' => $myVote ? $myVote->choice : null,
            'is_voting_open' => $proposal->is_voting_open,
            'participation' => [
                'total_eligible' => $totalEligible,
                'total_cast' => $totalCast,
                'percentage' => $totalEligible > 0 ? round(($totalCast / $totalEligible) * 100, 2) : 0,
                'minimum_quorum' => $proposal->minimum_quorum,
                'quorum_met' => $proposal->minimum_quorum ? ($totalCast >= $proposal->minimum_quorum) : true,
            ],
        ]);
    }

    public function vote(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user->isEligibleForShura()) {
            return response()->json(['message' => 'You are not eligible to vote at this time'], 403);
        }

        $proposal = ProjectProposal::findOrFail($id);

        if (!$proposal->is_voting_open) {
            return response()->json(['message' => 'Voting is not open for this proposal'], 422);
        }

        $validated = $request->validate([
            'choice' => 'required|string|in:yes,no',
        ]);

        $user = $request->user();

        // One vote per user per proposal
        $existing = ProjectProposalVote::where('project_proposal_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already voted on this proposal'], 409);
        }

        $weight = 1.00;
        if ($proposal->voting_type === 'share_percentage') {
            $eligibility = $user->savingsSharesEligibility();
            $weight = (float) ($eligibility['shares'] ?? 0);
            if ($weight <= 0) {
                return response()->json(['message' => 'You must have shares to vote on this proposal'], 403);
            }
        }

        $vote = ProjectProposalVote::create([
            'project_proposal_id' => $id,
            'user_id' => $user->id,
            'choice' => $validated['choice'],
            'weight' => $weight,
        ]);

        return response()->json([
            'message' => 'Vote recorded',
            'vote' => $vote
        ]);
    }

    public function storeComment(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user->isEligibleForShura()) {
            return response()->json(['message' => 'You are not eligible to post comments'], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $proposal = ProjectProposal::findOrFail($id);

        $comment = $proposal->comments()->create([
            'user_id' => $user->id,
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'message' => 'Comment posted successfully',
            'comment' => $comment->load('user:id,name')
        ], 201);
    }
}
