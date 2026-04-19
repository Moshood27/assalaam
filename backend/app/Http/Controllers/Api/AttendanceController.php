<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\AttendanceRecord;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\GeoService;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    protected GeoService $geoService;
    protected AttendanceService $attendanceService;

    public function __construct(GeoService $geoService, AttendanceService $attendanceService)
    {
        $this->geoService = $geoService;
        $this->attendanceService = $attendanceService;
    }

    public function current(Request $request)
    {
        $user = $request->user();

        // Ensure meetings statuses are up-to-date for accurate "auto-start/stop"
        $this->syncStatuses();

        // Find ongoing meeting
        $meeting = Meeting::where('status', 'ongoing')
            ->where(function ($query) use ($user) {
                $query->whereDoesntHave('branches')
                    ->orWhereHas('branches', function ($q) use ($user) {
                        $q->where('branches.id', $user->branch_id);
                    });
            })
            ->first();

        // If no ongoing, find the next scheduled one
        if (!$meeting) {
            $meeting = Meeting::where('status', 'scheduled')
                ->where(function ($query) use ($user) {
                    $query->whereDoesntHave('branches')
                        ->orWhereHas('branches', function ($q) use ($user) {
                            $q->where('branches.id', $user->branch_id);
                        });
                })
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->first();
        }

        if (!$meeting) {
            return response()->json([
                'meeting' => null,
                'attendance_record' => null,
                'message' => 'No active or upcoming meeting found'
            ]);
        }

        $record = $user->attendanceRecords()->where('meeting_id', $meeting->id)->first();

        return response()->json([
            'meeting' => $meeting,
            'attendance_record' => $record,
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $history = $user->attendanceRecords()
            ->with('meeting')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($history);
    }

    private function syncStatuses()
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        $now = now($timezone);
        $todayStr = $now->toDateString();
        $nowStr = $now->toTimeString();

        // Start meetings that should be ongoing
        Meeting::where('status', 'scheduled')
            ->where('date', '<=', $todayStr)
            ->where('start_time', '<=', $nowStr)
            ->where('end_time', '>', $nowStr)
            ->update(['status' => 'ongoing']);

        // End meetings that should be completed
        $completedCount = Meeting::whereIn('status', ['scheduled', 'ongoing'])
            ->where(function ($query) use ($todayStr, $nowStr) {
                $query->where('date', '<', $todayStr)
                    ->orWhere(function ($q) use ($todayStr, $nowStr) {
                        $q->where('date', $todayStr)
                            ->where('end_time', '<=', $nowStr);
                    });
            })
            ->update(['status' => 'completed']);

        if ($completedCount > 0) {
            // Immediately audit completed meetings to charge fines
            Artisan::call('app:audit-attendance');
        }
    }

    public function markAttendance(Request $request, Meeting $meeting)
    {
        $request->validate([
            'pin' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'device_uuid' => 'required|string',
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

        // One Person, One Vote: Check if this phone has already been used by someone else for THIS meeting
        $alreadyUsed = AttendanceRecord::where('meeting_id', $meeting->id)
            ->where('device_uuid', $request->device_uuid)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($alreadyUsed) {
            return response()->json([
                'message' => 'This device has already been used to mark attendance for another member in this meeting.'
            ], 403);
        }

        $record = AttendanceRecord::updateOrCreate(
            ['user_id' => $user->id, 'meeting_id' => $meeting->id],
            [
                'status' => 'present',
                'attended_at' => now(),
                'lat' => $request->lat,
                'lng' => $request->lng,
                'device_uuid' => $request->device_uuid,
            ]
        );

        $message = 'Attendance marked successfully';
        if ($this->attendanceService->isLate($meeting, $record->attended_at)) {
            $this->attendanceService->chargeLatenessFine($user, $meeting);
            $message .= '. You were late and charged a lateness fine of 100.';
        }

        return response()->json(['message' => $message, 'record' => $record]);
    }

}
