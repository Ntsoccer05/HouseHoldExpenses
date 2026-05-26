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
        // $schedule->command('inspire')->hourly();
        $schedule->command('fixed-expenses:replicate')
            ->monthlyOn(1, '10:00')
            ->timezone('Asia/Tokyo')
            ->onOneServer();

        $schedule->command('fixed-expenses:cleanup')
            ->monthlyOn(1, '10:30')
            ->timezone('Asia/Tokyo')
            ->onOneServer();
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
