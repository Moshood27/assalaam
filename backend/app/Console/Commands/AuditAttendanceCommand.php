<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditAttendanceCommand extends Command
{
    protected $signature = 'app:audit-attendance';
    protected $description = 'Audit completed meetings and charge fines for absent members';

    public function handle(AttendanceService $attendanceService)
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        $now = now($timezone);
        $todayStr = $now->toDateString();
        $nowStr = $now->toTimeString();

        // Audit meetings that are completed OR ongoing but time has passed
        $meetings = Meeting::where('status', 'completed')
            ->orWhere(function ($query) use ($todayStr, $nowStr) {
                $query->where('status', 'ongoing')
                    ->where(function ($q) use ($todayStr, $nowStr) {
                        $q->where('date', '<', $todayStr)
                            ->orWhere(function ($qq) use ($todayStr, $nowStr) {
                                $qq->where('date', $todayStr)
                                    ->where('end_time', '<=', $nowStr);
                            });
                    });
            })
            ->get();

        if ($meetings->isEmpty()) {
            $this->info("No meetings to audit.");
            return;
        }

        foreach ($meetings as $meeting) {
            // Use atomic update to prevent concurrent auditing if multiple instances are running
            // We include both 'completed' and 'ongoing' as valid statuses to claim
            $claimed = Meeting::where('id', $meeting->id)
                ->whereIn('status', ['completed', 'ongoing'])
                ->update(['status' => 'audited']); // We mark as audited immediately to claim it

            if (!$claimed) {
                continue;
            }

            $this->info("Auditing meeting: {$meeting->name} (ID: {$meeting->id})");

            // Define who should have attended (non-admins)
            $query = User::where('is_admin', false);
            if ($meeting->branches()->exists()) {
                $query->whereIn('branch_id', $meeting->branches()->pluck('branches.id'));
            }

            $users = $query->get();

            foreach ($users as $user) {
                $record = AttendanceRecord::where('meeting_id', $meeting->id)
                    ->where('user_id', $user->id)
                    ->first();

                // If no record, or status is still 'absent', charge fine
                // Possible statuses are 'present', 'fine_paid', 'fine_pending', or 'absent'
                if (!$record || $record->status === 'absent') {
                    $attendanceService->chargeAbsenceFine($user, $meeting, $record);
                    $this->line("Processed absence fine for User: {$user->full_name} (ID: {$user->id})");
                } else {
                    $this->line("Skipping User: {$user->full_name} (Status: {$record->status})");
                }
            }

            $this->info("Meeting {$meeting->name} audited successfully.");
        }
    }

    private function chargeFine(User $user, Meeting $meeting, $record)
    {
        // Removed as it is now in AttendanceService
    }
}
