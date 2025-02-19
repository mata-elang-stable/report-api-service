<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/', function () {
        return redirect('/login');
    });
    Route::get('/dashboard', [ReportController::class, 'index'])->name('dashboard');
    Route::get('/reports/download/{id}', [ReportController::class, 'downloadReport'])->name('download.report');
    Route::get('/reports/view/{id}', [ReportController::class, 'viewReport'])->name('view.report');
    Route::delete('/reports/{id}', [ReportController::class, 'destroy'])->name('delete.report');
    Route::get('/report-generate', [ReportController::class, 'generateReport'])->name('generate.report');
});

Route::get('/reports/{id}/view', [ReportController::class, 'viewReport'])->name('reports.view');
