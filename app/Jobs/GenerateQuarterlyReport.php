<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class GenerateQuarterlyReport implements ShouldQueue
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
        $startDate = Carbon::now()->subMonths(3)->startOfMonth()->toDateString();
        $endDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        Log::info('Dispatching GenerateReport job for quarterly report');
        GenerateReport::dispatch($startDate, $endDate, 'quarterly_report');
    }
}
