<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Models\Report;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/generate-report', [ReportController::class, 'generateReport'])->name('generate.report');
    Route::get('/reports', [DashboardController::class, 'getReports'])->name('reports');
    Route::get('/get-reports', [DashboardController::class, 'dashboard']);
    Route::get('/reports/download/{id}', [ReportController::class, 'downloadReport'])->name('download.report');
});


// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->get('/dashboard', function () {
//     return view('dashboard');
// })->name('dashboard');

// Route::post('/events', [ReportController::class, 'getReport']);

// // Route::get('/generate-report', [ReportController::class, 'generateReport'])->name('generate.report');

// Route::get('/dispatch-report', [ReportController::class, 'dispatchReportJob']);

// Route::get('/get-template/{id}', [ReportController::class, 'getReportTemplate']);

// // Route::get('/dashboard', [DashboardController::class, 'index']);

// Route::get('/get-reports', [DashboardController::class, 'getReports'])->name('get.reports');
