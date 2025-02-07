<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::post('/events', [ReportController::class, 'getReport']);

Route::get('/generate-report', [ReportController::class, 'generateReport'])->name('generate.report');

Route::get('/dispatch-report', [ReportController::class, 'dispatchReportJob']);

Route::get('/get-template/{id}', [ReportController::class, 'getReportTemplate']);
