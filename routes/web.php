<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// Google OAuth User Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected calendar routes - require authentication
    Route::get('/', [CalendarController::class, 'index'])->name('home');
    Route::get('/calendar', [CalendarController::class, 'show'])->name('calendar');
    Route::get('/all-calendars', [CalendarController::class, 'all'])->name('all-calendars');
    Route::get('/calendars.css', [CalendarController::class, 'css'])->name('calendars.css');
    Route::get('/docket.js', [CalendarController::class, 'js'])->name('docket.js');
    Route::get('/icalproxy', [CalendarController::class, 'icalProxy'])->name('icalproxy');
});
