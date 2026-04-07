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

// The SPA's login screen is the canonical 'login' route so that the auth
// middleware redirects unauthenticated requests directly to the React login
// page without an extra intermediate hop.
Route::get('/university/login', fn () => view('university'))->name('login');

// Compatibility redirect: /login → /university/login.
// Uses a path-absolute Location header so the browser resolves it against
// the same origin (scheme + host + port) it already used, avoiding any
// cross-port or cross-scheme redirect regardless of how the app is accessed.
Route::get('/login', fn () => redirect()->away('/university/login'));

Route::get('/university/{any?}', fn() => view('university'))->where('any', '.*');
