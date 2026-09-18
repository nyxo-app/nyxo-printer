<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get(config('nyxo-printer.download_route', 'downloads/Nyxo_Universal_Printer_Setup_Win.exe'), function () {
    $publishedPath = public_path('downloads/Nyxo_Universal_Printer_Setup_Win.exe');
    $packagePath = __DIR__.'/../resources/dist/Nyxo_Universal_Printer_Setup_Win.exe';

    $path = file_exists($publishedPath) ? $publishedPath : $packagePath;

    if (! file_exists($path)) {
        return redirect()->away('https://printer.nyxo.app');
    }

    return response()->download($path, 'Nyxo_Universal_Printer_Setup_Win.exe');
})->name('nyxo-printer.download');
