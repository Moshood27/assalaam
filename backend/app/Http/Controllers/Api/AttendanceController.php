<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\AttendanceRecord;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\GeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    protected GeoService $geoService;

    public function __construct(GeoService $geoService)
    {
        $this->geoService = $geoService;
    }

    public function current(Request $request)
    {
        $user = $request->user();

        // Find ongoing meeting
        $meeting = Meeting::where('status', 'ongoing')
            ->where(function ($query) use ($user) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            })
            ->first();

        // If no ongoing, find the next scheduled one
        if (!$meeting) {
            $meeting = Meeting::where('status', 'scheduled')
                ->where(function ($query) use ($user) {
                    $query->whereNull('branch_id')
                        ->orWhere('branch_id', $user->branch_id);
                })
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->first();
        }

        if (!$meeting) {
            return response()->json(['message' => 'No active or upcoming meeting found'], 404);
        }

        $record = $user->attendanceRecords()->where('meeting_id', $meeting->id)->first();

        return response()->json([
            'meeting' => $meeting,
            'attendance_record' => $record,
        ]);
    }

    public function markAttendance(Request $request, Meeting $meeting)
    {
        $request->validate([
            'pin' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($meeting->status !== 'ongoing') {
            return response()->json(['message' => 'Meeting is not ongoing'], 400);
        }

        if ($meeting->pin !== $request->pin) {
            return response()->json(['message' => 'Invalid PIN'], 400);
        }

        if (is_null($meeting->venue_lat) || is_null($meeting->venue_lng)) {
             return response()->json(['message' => 'Meeting venue location not set by admin'], 400);
        }

        // Check distance
        $distance = $this->geoService->calculateDistance(
            (float) $meeting->venue_lat,
            (float) $meeting->venue_lng,
            (float) $request->lat,
            (float) $request->lng
        );

        $radius = (int) ($meeting->radius_meters ?: config('cooperative.attendance.radius_meters', 50));
        if ($distance > $radius) {
            return response()->json([
                'message' => 'You are too far from the venue. You must be within ' . $radius . ' meters.',
                'distance' => round($distance, 2) . 'm'
            ], 400);
        }

        $user = $request->user();
        $record = AttendanceRecord::updateOrCreate(
            ['user_id' => $user->id, 'meeting_id' => $meeting->id],
            [
                'status' => 'present',
                'attended_at' => now(),
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]
        );

        return response()->json(['message' => 'Attendance marked successfully', 'record' => $record]);
    }

    public function payApologyFee(Request $request, Meeting $meeting)
    {
        $user = $request->user();

        if ($meeting->status !== 'scheduled') {
             return response()->json(['message' => 'Apology fee can only be paid before the meeting starts'], 400);
        }

        $record = $user->attendanceRecords()->where('meeting_id', $meeting->id)->first();
        if ($record && ($record->status === 'present' || $record->status === 'apology_paid')) {
            return response()->json(['message' => 'Already marked as present or apology already paid'], 400);
        }

        $amount = (float) $meeting->apology_fee_amount;

        return DB::transaction(function () use ($user, $meeting, $amount) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ((float)$lockedUser->balance < $amount) {
                return response()->json(['message' => 'Insufficient wallet balance'], 400);
            }

            $lockedUser->decrement('balance', $amount);

            $reference = 'APOLOGY_' . $meeting->id . '_' . Str::random(8);

            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'source' => 'attendance_apology',
                'withdrawable' => true,
                'meta' => [
                    'meeting_id' => $meeting->id,
                    'meeting_name' => $meeting->name,
                ],
            ]);

            $record = AttendanceRecord::updateOrCreate(
                ['user_id' => $lockedUser->id, 'meeting_id' => $meeting->id],
                [
                    'status' => 'apology_paid',
                    'apology_paid_at' => now(),
                ]
            );

            return response()->json(['message' => 'Apology fee paid successfully', 'record' => $record]);
        });
    }
}
