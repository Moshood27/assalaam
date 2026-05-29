<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\User;

class SendMeetingRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-meeting-reminders';
    protected $description = 'Send push notification reminders before a meeting starts';

    public function handle()
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        $now = now($timezone);

        // Find meetings starting in about 30 minutes that haven't been notified yet
        // We look for meetings scheduled for today where start_time is between 25 and 35 mins from now
        $targetStart = $now->copy()->addMinutes(30);
        $windowStart = $targetStart->copy()->subMinutes(5);
        $windowEnd = $targetStart->copy()->addMinutes(5);

        $meetings = Meeting::where('status', 'scheduled')
            ->whereNull('reminder_sent_at')
            ->whereDate('date', $now->toDateString())
            ->get()
            ->filter(function ($meeting) use ($windowStart, $windowEnd, $timezone) {
                // Combine date and start_time into a Carbon instance
                $startTimeStr = $meeting->start_time;
                if ($startTimeStr instanceof \DateTimeInterface) {
                    $startTimeStr = $startTimeStr->format('H:i:s');
                }

                $startAt = \Carbon\Carbon::parse($meeting->date->format('Y-m-d') . ' ' . $startTimeStr, $timezone);
                return $startAt->between($windowStart, $windowEnd);
            });

        if ($meetings->isEmpty()) {
            $this->info("No upcoming meetings for reminders.");
            return;
        }

        foreach ($meetings as $meeting) {
            $this->info("Sending reminders for meeting: {$meeting->name} (ID: {$meeting->id})");

            $title = "ðŸ“Œ Meeting Reminder: {$meeting->name}";
            $body = "Meeting starts at " . date('h:i A', strtotime($meeting->start_time)) . ". Please be at the venue on time to mark your attendance and avoid the ₦500 fine.";

            $meeting->notifyMembers($title, $body, [
                'type' => 'attendance_meeting',
                'meeting_id' => (string) $meeting->id,
                'action' => '/attendance'
            ]);

            $meeting->update(['reminder_sent_at' => now()]);
            $this->info("Successfully sent reminders for meeting: {$meeting->name}");
        }
    }
}
