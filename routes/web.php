<?php

use App\Http\Controllers\ReportDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'))->name('home');
Route::get('/reports/{report}/download', ReportDownloadController::class)->middleware('auth')->name('reports.download');
