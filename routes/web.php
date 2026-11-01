<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;

// Google OAuth User Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Public asset routes - CSS and JS must be accessible for page rendering
Route::get('/calendars.css', [CalendarController::class, 'css'])->name('calendars.css');
Route::get('/docket.js', [CalendarController::class, 'js'])->name('docket.js');

// Build/deploy info for the running container (APP_VERSION etc. are baked in by the Dockerfile)
Route::get('/version', function () {
    return response()->json(config('version'));
})->name('version');

// Web app manifest - served dynamically so its icons can point at Vite's hashed build output
Route::get('/site.webmanifest', function () {
    return response()->json([
        'name' => config('app.name'),
        'short_name' => config('app.name'),
        'icons' => [
            ['src' => Vite::asset('resources/icons/web-app-manifest-192x192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => Vite::asset('resources/icons/web-app-manifest-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
        ],
        'theme_color' => '#000000',
        'background_color' => '#000000',
        'display' => 'standalone',
    ])->header('Content-Type', 'application/manifest+json');
})->name('manifest');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected calendar routes - require authentication
    Route::get('/', [CalendarController::class, 'index'])->name('home');
    Route::get('/calendar', [CalendarController::class, 'show'])->name('calendar');
    Route::get('/all-calendars', [CalendarController::class, 'all'])->name('all-calendars');
    Route::get('/icalproxy', [CalendarController::class, 'icalProxy'])->name('icalproxy');

    // Calendar management page
    Route::get('/manage', [CalendarController::class, 'manage'])->name('manage');
});
