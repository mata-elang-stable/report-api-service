<?php

namespace App\Jobs;

use App\Jobs\GenerateReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Log;

class GenerateMonthlyReport extends GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // define attributes
    protected $templateName = 'monthly_report';

    /**
     * Create a new job instance.
     */
    public function __construct(int $month = null, int $year = null)
    {
        // input: month = 1, year = 2021
        // output: startDate = 2021-01-01 00:00:00, endDate = 2021-01-31 23:59:59

        $month = $month ?? Carbon::now()->subMonth()->month;
        $year = $year ?? Carbon::now()->year;

        if ($year > Carbon::now()->year && $month < 1 && $month > 12) {
            throw new \InvalidArgumentException('Input date is invalid');
        }

        if ($year == Carbon::now()->year && $month >= Carbon::now()->month) {
            throw new \InvalidArgumentException('Input date are same or greater than current date');
        }

        // TODO: validate input, month should be between 1 and 12 and year should not be greater than current year

        // TODO: validate input, input should not same or greater than current month

        // TODO: if year is not provided, use the current year

        // TODO: if month is not provided, use the current month

        // Create start and end date based on month and year provided
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        Log::info('Start Date: ' . $startDate);
        Log::info('Dispatching GenerateReport job for monthly report');

        parent::__construct($startDate, $endDate);
    }
}
