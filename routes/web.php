<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;

Route::get('/', [CalendarController::class, 'index'])->name('home');
Route::get('/calendar', [CalendarController::class, 'show'])->name('calendar');
Route::get('/all-calendars', [CalendarController::class, 'all'])->name('all-calendars');
Route::get('/calendars.css', [CalendarController::class, 'css'])->name('calendars.css');
Route::get('/docket.js', [CalendarController::class, 'js'])->name('docket.js');
Route::get('/icalproxy', [CalendarController::class, 'icalProxy'])->name('icalproxy');
Route::get('/token', [CalendarController::class, 'token'])->name('token');
