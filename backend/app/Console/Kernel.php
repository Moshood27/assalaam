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

        // Remind guarantors with pending decisions at least twice daily (09:00 and 16:00 server time)
        $schedule->command('loans:remind-guarantors')->twiceDaily(9, 16);

        // Check every minute for AGM sessions that just opened and notify members once
        $schedule->command('agm:notify-voting-open')->everyMinute();

        // Close expired AGM sessions and notify members once
        $schedule->command('agm:close-expired-sessions')->everyMinute();

        // Autosave Smart Savings: run daily at 08:00 Africa/Lagos; command internally checks weekday
        $schedule->command('autosave:charge')->dailyAt('08:00')->timezone('Africa/Lagos');

        // Takaful monthly contribution charge: on the 1st of every month at 08:10 Africa/Lagos
        $schedule->command('takaful:charge')->monthlyOn(1, '08:10')->timezone('Africa/Lagos');

        // The Hunter: hourly sweep to auto-recover overdue loan installments from wallet balances
        $schedule->command('loans:hunter-sweep')->hourly();

        // Murabaha Store: auto-deduct due installments daily at 03:00 Africa/Lagos
        $schedule->command('murabaha:sweep')->dailyAt('03:00')->timezone('Africa/Lagos');

        // VTU provider wallet health check: notify admins if balances drop below threshold
        $schedule->command('vtu:check-balances')->hourly()->timezone('Africa/Lagos');

        // Reconcile pending VTU transactions every 5 minutes and refund failures
        $schedule->job(new \App\Jobs\ReconcileUtilityTransactions)->everyFiveMinutes();

        // Automated daily backups of MySQL database to an external location (like AWS S3 or Dropbox)
        // Cleanup old backups daily at midnight
        $schedule->command('backup:clean')->dailyAt('00:00');
        // Run database and application files backup daily at 01:00
        $schedule->command('backup:run')->dailyAt('01:00');
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
