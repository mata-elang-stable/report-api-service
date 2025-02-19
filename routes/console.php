<?php

use App\Jobs\GenerateAnnualReport;
use App\Jobs\GenerateDailyReport;
use App\Jobs\GenerateMonthlyReport;
use App\Jobs\GenerateQuarterlyReport;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Schedule::job(new GenerateDailyReport)->dailyAt('00:00')->onSuccess(function () {
    Log::info('GenerateDailyReport job dispatched successfully at ' . now());
});

Schedule::job(new GenerateMonthlyReport)->monthlyOn(1, '00:00')->onSuccess(function () {
    Log::info('GenerateMonthlyReport job dispatched successfully at ' . now());
});

Schedule::job(new GenerateQuarterlyReport)->quarterlyOn(1, '00:00')->onSuccess(function () {
    Log::info('GenerateQuarterlyReport job dispatched successfully at ' . now());
});

Schedule::job(new GenerateAnnualReport)->yearlyOn(1, 1, '00:00')->onSuccess(function () {
    Log::info('GenerateAnnualReport job dispatched successfully at ' . now());
});
