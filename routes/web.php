<?php

use App\Livewire\QueueSummaryDashboard;
use App\Livewire\UsersSummaryDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard/queue', QueueSummaryDashboard::class)->name('dashboard.queue');
Route::get('/dashboard/users', UsersSummaryDashboard::class)->name('dashboard.users');
