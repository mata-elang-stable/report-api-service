<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class GenerateQuarterlyReport extends GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $templateName = 'quarterly_report';

    /**
     * Create a new job instance.
     */
    public function __construct(int $month = null, int $year = null)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Calculate the start and end dates for the last quarter
        if ($month === null || $year === null) {
            $quarter = intdiv($currentMonth - 1, 3);
            $startMonth = $quarter * 3 + 1;
            $startDate = Carbon::create($currentYear, $startMonth, 1)->subQuarter()->startOfQuarter();
            $endDate = Carbon::create($currentYear, $startMonth, 1)->subQuarter()->endOfQuarter();
        } else {
            if ($year > $currentYear || ($year == $currentYear && $month >= $currentMonth)) {
                throw new \InvalidArgumentException('Input date is invalid');
            }

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfQuarter();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfQuarter();
        }
        Log::info('Start Date: ' . $startDate);
        Log::info('Dispatching GenerateReport job for quarterly report');

        parent::__construct($startDate, $endDate);
    }
}
