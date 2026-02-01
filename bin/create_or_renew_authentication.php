<?php

/**
 * This is the main index file for the application.
 * php version 7.2
 *
 * @category CLI
 *
 * @author   "Nicholas Avenell" <nicholas@istic.net>
 * @license  BSD-3-Clause https://opensource.org/license/bsd-3-clause
 *
 * @link     https://docket.hubris.house
 */
define('HOME_DIR', __DIR__.'/..');
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../lib/gcal.lib.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

$account = $argv[1];

// Get the API client and construct the service object.
$client = getClient($account);

$service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = [
    'maxResults' => 10,
    'orderBy' => 'startTime',
    'singleEvents' => true,
    'timeMin' => date('c'),
];
$results = $service->events->listEvents($calendarId, $optParams);
$events = $results->getItems();

if (empty($events)) {
    echo "No upcoming events found.\n";
} else {
    echo "Upcoming events:\n";
    foreach ($events as $event) {
        $start = $event->start->dateTime;
        if (empty($start)) {
            $start = $event->start->date;
        }
        printf("%s (%s)\n", $event->getSummary(), $start);
    }
}
