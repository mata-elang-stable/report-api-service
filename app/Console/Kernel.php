<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule the monthly report job to run on the 1st day of every month
        // $schedule->job(new \App\Jobs\GenerateMonthlyReport)->monthlyOn(1, '00:00');

        // Schedule the quarterly report job to run on the 1st day of every quarter
        // $schedule->job(new \App\Jobs\GenerateQuarterlyReport)->quarterlyOn(1, '00:00');

        // Schedule the annual report job to run on the 1st day of every year
        // $schedule->job(new \App\Jobs\GenerateAnnualReport)->yearlyOn(1, '00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}