<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'location' => [
        'latitude' => env('MY_LAT', 51.5074),
        'longitude' => env('MY_LON', -0.1278),
    ],

    'google' => [
        // Default credentials file path (relative to storage/app directory)
        // Single shared credentials file used by all accounts
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', 'google/credentials.json'),
        // Automatically detect Google Cloud and use Application Default Credentials
        // Set to true/false to override auto-detection behavior
        'use_application_default_credentials' => env('GOOGLE_USE_APPLICATION_DEFAULT_CREDENTIALS'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/token'),
        'default_account' => env('GOOGLE_DEFAULT_ACCOUNT', 'default'),
        'scopes' => [
            'https://www.googleapis.com/auth/photoslibrary.readonly',
            'https://www.googleapis.com/auth/calendar.readonly',
        ],
    ],

    'calendar' => [
        // Cache TTL for calendar data in seconds (default: 15 minutes)
        'cache_ttl' => env('CALENDAR_CACHE_TTL', 15 * 60),
    ],

];
