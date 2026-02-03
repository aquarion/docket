<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Festival Configuration
    |--------------------------------------------------------------------------
    |
    | Define festivals and their date ranges. Each festival can have a callback
    | to determine if it's active, or simple start/end dates.
    |
    */

    'festivals' => [
        'easter' => [
            'name' => 'Easter',
            'type' => 'easter_calculation',
            'days_before' => 2, // Good Friday (2 days before Easter)
            'days_after' => 1,  // Easter Monday (1 day after Easter)
        ],

        'christmas' => [
            'name' => 'Christmas',
            'type' => 'month',
            'month' => 12,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode Override
    |--------------------------------------------------------------------------
    |
    | When debug mode is enabled, allow overriding the active festival via
    | query parameter: ?festival=easter or ?festival=christmas or ?festival=none
    |
    */

    'debug_override_enabled' => env('APP_DEBUG', false),
];
