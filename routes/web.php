<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\WatchSessionController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('v1')->group(function () {
    // Event routing
    Route::post('/events', [EventController::class, 'ingest'])
        ->withoutMiddleware([VerifyCsrfToken::class]); // disable CSRF for POST
    Route::get('/events/{eventId}/active-sessions', [EventController::class, 'activeSessions']);
    
    // Watch session routing
    // Get active session count for an event
    Route::get('watch-sessions/active-count/{eventId}/', [WatchSessionController::class, 'activeCount']);

    // Get session details for a given session ID
    Route::get('watch-sessions/{sessionId}', [WatchSessionController::class, 'show']);
});

require __DIR__.'/settings.php';
