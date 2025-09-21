<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloaderController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/', [DownloaderController::class, 'showForm']);

Route::post('/download', [DownloaderController::class, 'download']);

Route::get('/download', [DownloaderController::class, 'downloadFile'])->name('download.file');

Route::get('/download-progress/{jobId}', [DownloaderController::class, 'getProgress']);

Route::get('/serve-download/{jobId}', [DownloaderController::class, 'serveDownload']);

require __DIR__.'/auth.php';
