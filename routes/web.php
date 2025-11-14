<?php

use App\Http\Controllers\MyPdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SamplePdfController;
use App\Models\SamplePdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware('auth')->group(function () {
    // Home / Dashboard
    Route::get('/dashboard', function () {
        $pdfs = SamplePdf::orderBy('created_at', 'desc')->get();
        return inertia('Dashboard', [
            'pdfs' => $pdfs
        ]);
    })->name('dashboard');

    // Build PDF (POST)
    Route::post('/build-pdf', [MyPdfController::class, 'build'])->name('pdf.build');

    // (Optional) If you want a download route that deletes after download)
    Route::get('/download/{fileName}', [MyPdfController::class, 'download'])
        ->name('pdf.download');

    Route::get('/sample-pdfs', [SamplePdfController::class, 'index'])->name('sample-pdfs.index');
    Route::post('/sample-pdfs', [SamplePdfController::class, 'store'])->name('sample-pdfs.store');
    Route::delete('/sample-pdfs/{pdf}', [SamplePdfController::class, 'destroy'])->name('sample-pdfs.destroy');
});



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__ . '/auth.php';
