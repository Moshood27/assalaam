<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send reminders to defaulters every day at 08:00 server time
        $schedule->command('loans:send-default-reminders')->dailyAt('08:00');

        // Check every minute for AGM sessions that just opened and notify members once
        $schedule->command('agm:notify-voting-open')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
