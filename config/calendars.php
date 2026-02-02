<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mapbox API Token
    |--------------------------------------------------------------------------
    |
    | Your Mapbox API token for map-related features.
    |
    */

    'mapbox_token' => env('MAPBOX_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Google Calendars
    |--------------------------------------------------------------------------
    |
    | Configure your Google Calendar sources. Each calendar should have:
    | - name: Display name
    | - src: Google Calendar ID
    | - color: Hex color code
    | - emoji: (optional) Emoji icon
    |
    | Add as many calendars as you need. The array key is a unique identifier.
    |
    */

    'google_calendars' => [
        'holidays' => [
            'name' => 'Holidays in the UK',
            'src' => env('GCAL_HOLIDAYS_SRC', 'k6ihf65p5md3okg9fpu4r2q36qk80r7e@import.calendar.google.com'),
            'color' => '#865A5A',
            'emoji' => '🎉',
        ],
        // Add more Google Calendars here:
        // 'work' => [
        //     'name' => 'Work Calendar',
        //     'src' => env('GCAL_WORK_SRC'),
        //     'color' => '#0096ff',
        //     'emoji' => '💼',
        // ],
        // 'personal' => [
        //     'name' => 'Personal',
        //     'src' => env('GCAL_PERSONAL_SRC'),
        //     'color' => '#8347c6',
        //     'emoji' => '🏠',
        // ],
        // 'family' => [
        //     'name' => 'Family Events',
        //     'src' => env('GCAL_FAMILY_SRC'),
        //     'color' => '#ff6b6b',
        //     'emoji' => '👨‍👩‍👧‍👦',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | iCal Calendars
    |--------------------------------------------------------------------------
    |
    | Configure your iCal/ICS calendar sources. Each calendar should have:
    | - name: Display name
    | - src: iCal URL
    | - color: Hex color code
    | - emoji: (optional) Emoji icon
    |
    | Add as many calendars as you need. The array key is a unique identifier.
    |
    */

    'ical_calendars' => [
        // Example calendars:
        // 'work_ical' => [
        //     'name' => 'Work Calendar',
        //     'src' => env('ICAL_WORK_URL'),
        //     'color' => '#0096ff',
        //     'emoji' => '💼',
        // ],
        // 'birthdays' => [
        //     'name' => 'Birthdays',
        //     'src' => env('ICAL_BIRTHDAYS_URL'),
        //     'color' => '#ff69b4',
        //     'emoji' => '🎂',
        // ],
        // 'deadlines' => [
        //     'name' => 'Project Deadlines',
        //     'src' => env('ICAL_DEADLINES_URL'),
        //     'color' => '#ff4444',
        //     'emoji' => '⏰',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Merged Calendars
    |--------------------------------------------------------------------------
    |
    | Define colors for merged/overlapping events. Use calendar IDs separated
    | by dashes to define the merge key.
    |
    | Example: If you have 'work' and 'personal' calendars, you can define
    | a color for when events from both overlap: 'work-personal'
    |
    */

    'merged_calendars' => [
        // Example:
        // 'work-personal' => [
        //     'color' => '#8347c6',
        // ],
        // 'family-holidays' => [
        //     'color' => '#ff6b6b',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar Filter
    |--------------------------------------------------------------------------
    |
    | List of calendar IDs or event patterns to filter out from display.
    |
    */

    'filter_out' => [],

    /*
    |--------------------------------------------------------------------------
    | Calendar Sets
    |--------------------------------------------------------------------------
    |
    | Define named groups of calendars that can be toggled together.
    | Use '*' to include all calendars. Calendar IDs should match the keys
    | in google_calendars and ical_calendars arrays.
    |
    | Example:
    | 'work' => [
    |     'name' => 'Work Calendars',
    |     'calendars' => ['work', 'holidays'],
    | ]
    |
    */

    'calendar_sets' => [
        'all' => [
            'name' => 'All Calendars',
            'calendars' => ['*'],
            'emoji' => '📅',
        ],
        // Add more calendar sets in etc/config/calendars.php to override
        // Example:
        // 'work' => [
        //     'name' => 'Work Only',
        //     'calendars' => ['work', 'holidays'],
        //     'emoji' => '💼',
        // ],
        // 'personal' => [
        //     'name' => 'Personal',
        //     'calendars' => ['personal', 'family', 'holidays'],
        //     'emoji' => '🏠',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Calendar Set
    |--------------------------------------------------------------------------
    |
    | The default calendar set to use when no 'version' parameter is provided.
    |
    */

    'default_calendar_set' => 'all',

];
