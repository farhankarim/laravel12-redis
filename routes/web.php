<?php

use App\Livewire\QueueSummaryDashboard;
use App\Livewire\UsersSummaryDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Email verification endpoint — validates the time-limited HMAC signature generated
// by QueuedEmailVerificationNotification before processing the verification.
Route::get('/email/verify', function (Request $request) {
    abort_unless($request->hasValidSignature(), 403);

    return response()->json(['message' => 'Email verified successfully.']);
})->name('email.verify');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/queue', QueueSummaryDashboard::class)->name('dashboard.queue');
    Route::get('/dashboard/users', UsersSummaryDashboard::class)->name('dashboard.users');
});

// Minimal login stub so the auth middleware can redirect unauthenticated
// requests without throwing a RouteNotFoundException.
// Replace this with a real authentication system (e.g. Laravel Breeze).
Route::get('/login', fn () => response('Unauthorized — please log in.', 401))->name('login');

Route::get('/university/{any?}', fn() => view('university'))->where('any', '.*');
