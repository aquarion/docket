<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['status' => 'ok'];
});

// API routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Protected calendar endpoints
    Route::get('/calendars', [\App\Http\Controllers\CalendarController::class, 'apiIndex']);
});

// Public API routes for Google OAuth
Route::get('/auth/google/url', [\App\Http\Controllers\LoginController::class, 'getGoogleAuthUrl']);
Route::post('/auth/google/token', [\App\Http\Controllers\LoginController::class, 'handleGoogleToken']);
