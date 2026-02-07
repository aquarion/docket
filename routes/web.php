<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalendarController::class, 'index'])->name('home');
Route::get('/calendar', [CalendarController::class, 'show'])->name('calendar');
Route::get('/all-calendars', [CalendarController::class, 'all'])->name('all-calendars');
Route::get('/calendars.css', [CalendarController::class, 'css'])->name('calendars.css');
Route::get('/docket.js', [CalendarController::class, 'js'])->name('docket.js');
Route::get('/icalproxy', [CalendarController::class, 'icalProxy'])->name('icalproxy');

// Google OAuth User Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Google Calendar API Authentication routes
Route::get('/token', [AuthController::class, 'token'])->name('token')->middleware('throttle:10,1');
Route::get('/auth/google/authorize', [AuthController::class, 'authorize'])->name('auth.google.authorize')->middleware('throttle:10,1');
Route::get('/auth/google/status', [AuthController::class, 'status'])->name('auth.google.status');
Route::get('/auth/google/check', [CalendarController::class, 'checkAuth'])->name('auth.google.check');
Route::delete('/auth/google/revoke', [AuthController::class, 'revoke'])->name('auth.google.revoke');
