<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MeetingApologyController extends Controller
{
    /**
     * Submit an apology for a meeting before it starts.
     */
    public function store(Request $request, Meeting $meeting)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'excuse_type' => 'required|string|in:medical,pregnancy,travel,official,other',
            'proof' => [
                $request->excuse_type === 'medical' || $request->excuse_type === 'pregnancy' || $request->excuse_type === 'travel' ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        $now = now($timezone);
        $startTime = Carbon::parse($meeting->date->format('Y-m-d') . ' ' . $meeting->start_time, $timezone);

        // Check if meeting has already started
        if ($now->isAfter($startTime)) {
            return response()->json([
                'message' => 'Apologies must be submitted before the meeting starts.'
            ], 400);
        }

        $user = $request->user();

        // Check if user is even supposed to attend this meeting (branch check)
        if ($meeting->branches()->exists()) {
            $isMemberOfBranch = $meeting->branches()->where('branches.id', $user->branch_id)->exists();
            if (!$isMemberOfBranch) {
                return response()->json([
                    'message' => 'You are not required to attend this meeting.'
                ], 403);
            }
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('excuse_proofs', 'public');
        }

        $record = AttendanceRecord::updateOrCreate(
            ['user_id' => $user->id, 'meeting_id' => $meeting->id],
            [
                'status' => 'pending_excuse',
                'excuse_reason' => $request->reason,
                'excuse_type' => $request->excuse_type,
                'excuse_proof_path' => $proofPath,
                'excused_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Your apology has been submitted and is pending admin approval.',
            'record' => $record
        ]);
    }
}
