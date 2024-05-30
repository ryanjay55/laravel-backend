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
        // Schedule the UpdateDeferralStatus command to run daily at midnight
        // $schedule->command('app:update-deferral-status')
        //     ->dailyAt('00:00');

        // Schedule the SendDonationReminders command to run daily at midnight
        $schedule->command('send:donation-reminders')
            ->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
