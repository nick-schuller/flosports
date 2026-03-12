<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Api\V1\EventController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('v1')->group(function () {
    Route::post('/events', [EventController::class, 'ingest'])
        ->withoutMiddleware([VerifyCsrfToken::class]); // disable CSRF for POST
    Route::get('/events/{eventId}/active-sessions', [EventController::class, 'activeSessions']);
    Route::get('/sessions/{sessionId}', [EventController::class, 'sessionDetails']);
});

require __DIR__.'/settings.php';
