<?php

use App\Livewire\QueueSummaryDashboard;
use App\Livewire\UsersSummaryDashboard;
use App\Http\Controllers\ChequebookImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/chequebook/import', [ChequebookImportController::class, 'dispatch']);
Route::get('/chequebook/import/sync', [ChequebookImportController::class, 'dispatchSync']);
Route::get('/chequebook/import/{batchId}', [ChequebookImportController::class, 'status']);
Route::get('/dashboard/queue', QueueSummaryDashboard::class)->name('dashboard.queue');
Route::get('/dashboard/users', UsersSummaryDashboard::class)->name('dashboard.users');
