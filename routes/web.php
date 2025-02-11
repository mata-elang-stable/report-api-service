<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Models\Report;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/', function () {
        return redirect('/login');
    });
    Route::get('/dashboard', [ReportController::class, 'index'])->name('dashboard');
    Route::get('/reports/download/{id}', [ReportController::class, 'downloadReport'])->name('download.report');
    Route::delete('/reports/{id}', [ReportController::class, 'destroy'])->name('delete.report');
});
