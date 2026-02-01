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
    'calendar' => [
      'api_key' => env('GOOGLE_API_KEY'),
      'client_id' => env('GOOGLE_CLIENT_ID'),
      'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],
  ],

];
