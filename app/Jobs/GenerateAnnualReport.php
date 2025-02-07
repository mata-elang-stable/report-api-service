<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class GenerateAnnualReport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $startDate = Carbon::now()->subYear()->startOfYear()->toDateString();
        $endDate = Carbon::now()->subYear()->endOfYear()->toDateString();

        Log::info('Dispatching GenerateReport job for annual report');
        GenerateReport::dispatch($startDate, $endDate, 'annual_report');
    }
}
