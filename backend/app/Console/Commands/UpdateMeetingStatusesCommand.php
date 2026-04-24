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

        // 1. Mark as completed if end_time has passed
        // This is done first to ensure that meetings that have ended are processed
        // even if the ongoing notification below takes a long time.
        $completedMeetings = Meeting::whereIn('status', ['scheduled', 'ongoing'])
            ->where(function ($query) use ($todayStr, $nowStr) {
                $query->where('date', '<', $todayStr)
                    ->orWhere(function ($q) use ($todayStr, $nowStr) {
                        $q->where('date', $todayStr)
                            ->where('end_time', '<=', $nowStr);
                    });
            })
            ->get();

        foreach ($completedMeetings as $meeting) {
            $meeting->update(['status' => 'completed']);
            $this->info("Marked meeting '{$meeting->name}' as completed.");
        }

        if ($completedMeetings->isNotEmpty()) {
            // Immediately audit completed meetings to charge fines
            $this->call('app:audit-attendance');
        }

        // 2. Mark as ongoing if current time is within window
        $meetingsToOngoing = Meeting::where('status', 'scheduled')
            ->where('date', '<=', $todayStr)
            ->where('start_time', '<=', $nowStr)
            ->where('end_time', '>', $nowStr)
            ->get();

        foreach ($meetingsToOngoing as $meeting) {
            $meeting->update(['status' => 'ongoing']);

            $this->info("Marked meeting '{$meeting->name}' as ongoing.");
        }
    }
}
