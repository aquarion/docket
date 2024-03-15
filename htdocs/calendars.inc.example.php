<?php

/**
 * File: Config.example.inc.php
 *
 * Includeable file containing calendar config
 *
 * @category config
 * @package  Docket
 * @author   aquarion <hello@istic.net>
 * @license  n/a
 * @link     https://github.com/aquarion/docket
 */



define("MAPBOX_API_TOKEN", "XXX"); //

define("REDIS_HOST", false); // Leave this false if you don't have redis

// define("REDIS_HOST", "172.17.0.1");
// define("REDIS_PORT", "6379");
// define("REDIS_PASSWORD", false);


$google_calendars = array(

    'holidays' => array( // Unique identifier
        'name' => 'Holidays in the UK', // Name to display in the key
        'src' => "k6ihf65p5md3okg9fpu4r2q36qk80r7e@import.calendar.google.com", // GCal UID
        'color' => '#865A5A' // Hexidecimal Hue
        ),
    // '' => array(
    //     'src' => "",
    //     'color' => '#'
    //     ),
);

$ical_calendars = array(
    'uniq_id' => array(
        'name' => "Display Name",
        'src'  => "ICal URL",
        'color' => '#0096ff',
        'emoji' => '🌀'
    ),

);


// merge ids from the above with a dash between them to define a new colour for shared events
$merged_calendars = array(
    "aquarion-fyr" => array(
        'color' => "#8347c6"
    )
);

$filter_out = array();

// FOr distance calculations
define("MY_LAT", "1");
define("MY_LON", "1");
