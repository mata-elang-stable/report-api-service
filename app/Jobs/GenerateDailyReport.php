<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Log;

class GenerateDailyReport extends GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $templateName = 'daily_report';
    /**
     * Create a new job instance.
     */
    public function __construct(int $day = null, int $month = null, int $year = null)
    {
        $day = $day ?? Carbon::now()->day;
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        if ($year > Carbon::now()->year && $month < 1 && $month > 12 && $day < 1 && $day > 31) {
            throw new \InvalidArgumentException('Input date is invalid');
        }

        if ($year == Carbon::now()->year && $month >= Carbon::now()->month && $day > Carbon::now()->day) {
            throw new \InvalidArgumentException('Input date are same or greater than current date');
        }

        $startDate = Carbon::createFromDate($year, $month, $day)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, $day)->endOfDay();

        Log::info('Dispatching GenerateReport job for daily report');
        // dd($startDate, $endDate);
        parent::__construct($startDate, $endDate);
    }
}
