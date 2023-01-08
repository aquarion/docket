<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/gcal.lib.php';
require __DIR__ . '/lib/radiator.lib.php';

use GuzzleHttp\Client;

// Handle parameters

$input_cal = strip_tags($_GET['cal']);

if (!isset($input_cal)) {
    throw new Exception("Cal not set");
}

if (!isset($ical_calendars[$input_cal ])) {
    throw new Exception("Cal not found");
}

// No
// Fetch calendar data
$calendar = $ical_calendars[$input_cal ];

if (REDIS_HOST) {
    // Setup Caching
    $redis = new Redis();
    //Connecting to Redis
    $redis->connect(REDIS_HOST, REDIS_PORT);
    if (REDIS_PASSWORD) {
        $redis->auth(REDIS_PASSWORD);
    }

    if ($output = $redis->get($input_cal)) {
        if ($output != "Object") {
            header("X-Cached: Yes: $input_cal");
            header("Content-Type: text/calendar; charset=utf-8");
            echo $output;
            // var_dump($output);
            exit;
        } else {
            header("X-Cached: Stupid. ");
        }
    } else {
        header("X-Cached: Not found: $input_cal");
    }
} else {
    header("X-Cached: No Redis Host: $input_cal");
}


// Fetch data
$client = new Client();

$res = $client->request(
    'GET',
    $calendar['src']
);

if (!$res->getStatusCode() == 200) {
    throw new Exception("Error accessing calendar");
}


if (REDIS_HOST) {
    $redis->setex($input_cal, 3600/2, $res->getBody()->getContents());
}

header("Content-Type: ". $res->getHeader('content-type')[0]);
echo $res->getBody();
