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
            'callback' => function () {
                $year = (int) date('Y');
                $easterSunday = easter_date($year);
                $goodFriday = strtotime('-2 days', $easterSunday);
                $easterMonday = strtotime('+1 day', $easterSunday);
                $today = strtotime('today');

                return $today >= $goodFriday && $today <= $easterMonday;
            },
        ],

        'christmas' => [
            'name' => 'Christmas',
            'callback' => function () {
                $month = (int) date('m');

                return $month === 12;
            },
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
