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
        // Default credentials file path
        // Account-specific credentials can be placed at: etc/credentials_{account}.json
        // Example: etc/credentials_aqcom.json for account 'aqcom'
        // Falls back to this default if account-specific file doesn't exist
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', base_path('etc/credentials.json')),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/token'),
        'scopes' => [
            'https://www.googleapis.com/auth/photoslibrary.readonly',
            'https://www.googleapis.com/auth/calendar.readonly',
        ],
    ],

];
