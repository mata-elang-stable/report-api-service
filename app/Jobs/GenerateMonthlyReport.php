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
        $month = $month ?? Carbon::now()->subMonth()->month;
        $year = $year ?? Carbon::now()->year;

        if ($year > Carbon::now()->year && $month < 1 && $month > 12) {
            throw new \InvalidArgumentException('Input date is invalid');
        }

        if ($year == Carbon::now()->year && $month >= Carbon::now()->month) {
            throw new \InvalidArgumentException('Input date are same or greater than current date');
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        Log::info('Dispatching GenerateReport job for monthly report');

        parent::__construct($startDate, $endDate);
    }
}
