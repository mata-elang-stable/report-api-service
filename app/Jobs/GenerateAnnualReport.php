<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class GenerateAnnualReport extends GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $templateName = 'annual_report';
    /**
     * Create a new job instance.
     */
    public function __construct(int $year = null)
    {
        $currentYear = Carbon::now()->year;

        // Calculate the start and end dates for the last year
        if ($year === null) {
            $startDate = Carbon::now()->subYear()->startOfYear();
            $endDate = Carbon::now()->subYear()->endOfYear();
        } else {
            if ($year > $currentYear) {
                throw new \InvalidArgumentException('Input date is invalid');
            }

            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($year, 1, 1)->endOfYear();
        }

        Log::info('Start Date: ' . $startDate);
        Log::info('Dispatching GenerateReport job for annual report');

        parent::__construct($startDate, $endDate);
    }
}
