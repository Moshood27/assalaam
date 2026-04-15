<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;

class UpdateMeetingStatusesCommand extends Command
{
    protected $signature = 'app:update-meeting-statuses';
    protected $description = 'Automatically update meeting statuses based on current time';

    public function handle()
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        $now = now($timezone);
        $todayStr = $now->toDateString();
        $nowStr = $now->toTimeString();

        $this->info("Current time ({$timezone}): {$todayStr} {$nowStr}");

        // Mark as ongoing if current time is within window
        $meetingsToOngoing = Meeting::where('status', 'scheduled')
            ->where('date', '<=', $todayStr)
            ->where('start_time', '<=', $nowStr)
            ->where('end_time', '>', $nowStr)
            ->get();

        foreach ($meetingsToOngoing as $meeting) {
            $meeting->update(['status' => 'ongoing']);

            // Notify members that it's time for the meeting
            $meeting->notifyMembers(
                "⏰ Meeting Time: {$meeting->name}",
                "The meeting is starting now. Please join or mark your attendance.",
                ['type' => 'meeting_ongoing']
            );

            $this->info("Marked meeting '{$meeting->name}' as ongoing and notified members.");
        }

        $ongoingCount = $meetingsToOngoing->count();

        // Mark as completed if end_time has passed
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
            $this->info("Marked {$completedCount} meetings as completed.");
            // Immediately audit completed meetings to charge fines
            $this->call('app:audit-attendance');
        }
    }
}
