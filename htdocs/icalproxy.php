<?php

/**
 * This is the main index file for the application.
 * php version 7.2
 *
 * @category Personal
 *
 * @author   "Nicholas Avenell" <nicholas@istic.net>
 * @license  BSD-3-Clause https://opensource.org/license/bsd-3-clause
 *
 * @link     https://docket.hubris.house
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('HOME_DIR', __DIR__.'/..');
define('SEND_JSON_ERRORS', true);
define('SEND_TEXT_ERRORS', false);

require HOME_DIR.'/vendor/autoload.php';
require HOME_DIR.'/lib/gcal.lib.php';
require HOME_DIR.'/lib/docket.lib.php';

use GuzzleHttp\Client;

// Handle parameters

define('RAW_OUTPUT', isset($_GET['raw']) ? true : false);
define('USE_CACHE', isset($_GET['nocache']) ? false : true);

if (! isset($_GET['cal'])) {
    throw new Exception('No cal specified');
}
// Cal
$input_cal = strip_tags($_GET['cal']);

if (! isset($input_cal)) {
    throw new Exception('Cal not set');
}

if (! isset($ical_calendars[$input_cal])) {
    throw new Exception('Cal not found');
}

if (REDIS_HOST && USE_CACHE) {
    // define('USE_REDIS', true);
    define('USE_REDIS', false);
} else {
    define('USE_REDIS', false);
}

if (RAW_OUTPUT) {
    header('Content-Type: text/plain; charset=utf-8');
}

// No
// Fetch calendar data
$calendar = $ical_calendars[$input_cal];

try {
    if (USE_REDIS) {
        // Setup Caching
        $redis = new Redis;
        // Connecting to Redis
        $redis->connect(REDIS_HOST, REDIS_PORT);
        if (REDIS_PASSWORD) {
            $redis->auth(REDIS_PASSWORD);
        }

        if ($output = $redis->get($input_cal)) {
            if ($output != 'Object') {
                header("X-Cached: Yes: $input_cal");
                header('Content-Type: text/calendar; charset=utf-8');
                echo $output;
                // var_dump($output);
                exit;
            } else {
                header('X-Cached: Stupid. ');
            }
        } else {
            header("X-Cached: Not found: $input_cal");
        }
    } else {
        header("X-Cached: No Redis Host: $input_cal");
    }
} catch (Exception $e) {
    // No Redis
    header('X-Cached: No Redis: '.$e->getMessage());
}

// Fetch data
$client = new Client;

$res = $client->request(
    'GET',
    $calendar['src']
);

if (! $res->getStatusCode() == 200) {
    throw new Exception('Error accessing calendar');
}

if (USE_REDIS) {
    $redis->setex($input_cal, 3600 / 2, $res->getBody()->getContents());
}

if (! RAW_OUTPUT) {
    header('Content-Type: '.$res->getHeader('content-type')[0]);
}
echo $res->getBody();
