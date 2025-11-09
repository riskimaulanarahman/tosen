<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Clean up expired OTP tokens every hour
        $schedule->command('app:cleanup-expired-otp-tokens')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old selfies daily at 2 AM
        $schedule->command('attendance:cleanup-selfies --days=90 --force')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Monitor attendance patterns weekly on Sunday at 3 AM
        $schedule->command('attendance:monitor-patterns --days=7 --export')
            ->weeklyOn(0, '03:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Monthly pattern analysis on 1st day of month
        $schedule->command('attendance:monitor-patterns --days=30 --export')
            ->monthlyOn(1, '04:00')
            ->withoutOverlapping()
            ->runInBackground();
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
