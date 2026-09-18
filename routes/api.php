<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nyxo\Printer\Http\Controllers\PrintJobApiController;
use Nyxo\Printer\Http\Middleware\CheckPrintToken;

Route::middleware([CheckPrintToken::class])->group(function () {
    Route::get('/ping', [PrintJobApiController::class, 'ping'])->name('nyxo-printer.ping');
    Route::get('/jobs', [PrintJobApiController::class, 'index'])->name('nyxo-printer.jobs');
    Route::post('/jobs/{job}/status', [PrintJobApiController::class, 'updateStatus'])->name('nyxo-printer.jobs.status');
});
