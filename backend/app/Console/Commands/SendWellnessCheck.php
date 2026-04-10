<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Notifications\WellnessCheckNotification;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;

class SendWellnessCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-wellness-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends wellness check notifications to inactive members and alerts admins of potential deceased members.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $months = config('cooperative.legacy.inactivity_months', 6);
        $threshold = now()->subMonths($months);

        // 1. Find users inactive for more than X months who haven't been notified yet
        $usersToNotify = User::whereNull('deceased_at')
            ->where(function($q) use ($threshold) {
                $q->where('last_activity_at', '<', $threshold)
                  ->orWhereNull('last_activity_at'); // also catch those who never logged in since the field was added
            })
            // Filter those who haven't been notified yet OR who were notified but logged in since then and are now inactive again
            ->where(function($q) {
                $q->whereNull('wellness_check_notified_at')
                  ->orWhereColumn('wellness_check_notified_at', '<', 'last_activity_at');
            })
            ->get();

        foreach ($usersToNotify as $user) {
            $this->info("Sending wellness check to user: {$user->id} ({$user->name})");
            $user->notify(new WellnessCheckNotification());
            $user->update(['wellness_check_notified_at' => now()]);
        }

        // 2. Find users who were notified more than config('check_period_days') ago and still haven't logged in
        $alertPeriodDays = config('cooperative.legacy.check_period_days', 30);
        $alertThreshold = now()->subDays($alertPeriodDays);

        $suspectedDeceased = User::whereNull('deceased_at')
            ->whereNotNull('wellness_check_notified_at')
            ->where('wellness_check_notified_at', '<', $alertThreshold)
            ->where(function($q) {
                // Not logged in since the last notification
                $q->whereNull('last_activity_at')
                  ->orWhereColumn('last_activity_at', '<', 'wellness_check_notified_at');
            })
            ->get();

        if ($suspectedDeceased->isNotEmpty()) {
            $adminUsers = User::role('Admin')->get(); // Assuming 'Admin' role exists
            if ($adminUsers->isEmpty()) {
                $adminUsers = User::where('is_admin', true)->get();
            }

            foreach ($suspectedDeceased as $user) {
                $this->warn("User {$user->id} ({$user->name}) still inactive after wellness check. Alerting Admin.");

                Notification::send($adminUsers, new GeneralNotification(
                    title: 'Potential Deceased Member Alert',
                    message: "Member {$user->name} ({$user->membership_number}) has been inactive for a long time and did not respond to the wellness check sent on {$user->wellness_check_notified_at->format('Y-m-d')}.",
                    data: [
                        'user_id' => $user->id,
                        'route' => "/admin/users/{$user->id}/edit"
                    ]
                ));
            }
        }

        return 0;
    }
}
