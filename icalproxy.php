<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/gcal.lib.php';
require __DIR__ . '/lib/radiator.lib.php';

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;

// Set up caching
$stack = HandlerStack::create();
$stack->push(new CacheMiddleware(), 'cache');
$client = new Client(['handler' => $stack]);

// Handle parameters

$input_cal = strip_tags($_GET['cal']);

if (!isset($_GET['cal'])) {
    throw new Exception("Cal not set");
}

if (!isset($ical_calendars[$_GET['cal']])) {
    throw new Exception("Cal not found");
}

// Fetch calendar data
$calendar = $ical_calendars[$_GET['cal']];

// Fetch data
$client = new Client();
$res = $client->request(
    'GET',
    $calendar['src']
);

if (!$res->getStatusCode() == 200) {
    throw new Exception("Error accessing calendar");
}

// Display

header("Content-Type: ". $res->getHeader('content-type')[0]);
echo $res->getBody();
