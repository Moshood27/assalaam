<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgmSession;
use App\Models\AgmCandidate;
use App\Models\AgmVote;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AgmController extends Controller
{
    public function sessions(Request $request)
    {
        $now = Carbon::now();
        $sessions = AgmSession::query()
            ->where(function ($q) use ($now) {
                $q->where('status', 'open')
                  ->orWhere(function ($q2) use ($now) {
                      $q2->whereNotNull('start_at')->whereNotNull('end_at')
                         ->where('start_at', '<=', $now)
                         ->where('end_at', '>=', $now);
                  });
            })
            ->orderByDesc('start_at')
            ->limit(10)
            ->get();
        return response()->json($sessions);
    }

    public function candidates(Request $request, int $id)
    {
        $session = AgmSession::findOrFail($id);
        $candidates = AgmCandidate::query()->where('session_id', $session->id)->orderBy('position')->orderBy('name')->get();
        // Group by position for convenience
        $grouped = [];
        foreach ($candidates as $c) {
            $grouped[$c->position][] = $c;
        }
        // Include info about existing votes per position for this user
        $user = $request->user();
        $myVotes = AgmVote::query()->where('user_id', $user->id)->where('session_id', $session->id)->get()->keyBy('position');
        return response()->json([
            'session' => $session,
            'positions' => array_map(function ($pos, $list) use ($myVotes) {
                return [
                    'position' => $pos,
                    'candidates' => array_values(array_map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'position' => $c->position,
                        'manifesto' => $c->manifesto,
                        'photo_url' => $c->photo_url,
                    ], $list)),
                    'voted_candidate_id' => optional($myVotes->get($pos))->candidate_id,
                ];
            }, array_keys($grouped), array_values($grouped)),
        ]);
    }

    public function vote(Request $request, int $id)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|integer|exists:agm_candidates,id',
        ]);
        $user = $request->user();

        $session = AgmSession::findOrFail($id);
        if ($session->status !== 'open') {
            // if times are set, ensure within window
            $now = Carbon::now();
            $within = $session->start_at && $session->end_at && $now->between($session->start_at, $session->end_at);
            if (!$within) {
                return response()->json(['message' => 'Voting is closed for this session'], 422);
            }
        }

        $candidate = AgmCandidate::query()->where('id', $validated['candidate_id'])->where('session_id', $session->id)->firstOrFail();
        $position = $candidate->position;

        // One vote per user per position per session
        $existing = AgmVote::query()
            ->where('user_id', $user->id)
            ->where('session_id', $session->id)
            ->where('position', $position)
            ->first();
        if ($existing) {
            return response()->json(['message' => 'You have already voted for ' . $position], 409);
        }

        $vote = AgmVote::create([
            'user_id' => $user->id,
            'session_id' => $session->id,
            'candidate_id' => $candidate->id,
            'position' => $position,
        ]);

        return response()->json(['message' => 'Vote recorded', 'vote' => $vote]);
    }

    public function results(Request $request, int $id)
    {
        $session = AgmSession::findOrFail($id);
        $rows = AgmVote::query()
            ->select('position', 'candidate_id', DB::raw('COUNT(*) as votes'))
            ->where('session_id', $session->id)
            ->groupBy('position', 'candidate_id')
            ->get();

        $candidates = AgmCandidate::query()->where('session_id', $session->id)->get()->keyBy('id');

        $byPosition = [];
        foreach ($rows as $r) {
            $pos = $r->position;
            $cid = $r->candidate_id;
            $byPosition[$pos][] = [
                'candidate_id' => $cid,
                'candidate_name' => optional($candidates->get($cid))->name,
                'votes' => (int) $r->votes,
            ];
        }
        // Ensure positions with zero votes still appear with zero counts
        foreach ($candidates as $cand) {
            $pos = $cand->position;
            if (!isset($byPosition[$pos])) $byPosition[$pos] = [];
            $exists = false;
            foreach ($byPosition[$pos] as $e) { if ($e['candidate_id'] === $cand->id) { $exists = true; break; } }
            if (!$exists) {
                $byPosition[$pos][] = [
                    'candidate_id' => $cand->id,
                    'candidate_name' => $cand->name,
                    'votes' => 0,
                ];
            }
        }

        // Sort each position group by votes desc
        foreach ($byPosition as $pos => &$list) {
            usort($list, fn($a, $b) => $b['votes'] <=> $a['votes']);
        }
        unset($list);

        return response()->json([
            'session' => $session,
            'results' => $byPosition,
        ]);
    }
}
