<?php
/**
 * This is the main index file for the application.
 * php version 7.2
 *
 * @category Personal
 * @package  Radiator
 * @author   "Nicholas Avenell" <nicholas@istic.net>
 * @license  BSD-3-Clause https://opensource.org/license/bsd-3-clause
 * @link     https://docket.hubris.house
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


define('HOME_DIR', __DIR__.'/..');

require HOME_DIR . '/vendor/autoload.php';
require HOME_DIR . '/lib/gcal.lib.php';
require HOME_DIR . '/lib/radiator.lib.php';

define('SEND_JSON_ERRORS', true);

define('SEND_TEXT_ERRORS', false);

// Get the API client and construct the service object.
$client = getClient();
$cxn_gcal = new Google_Service_Calendar($client);

// Print the next events on the user's calendar.

if (!isset($_GET['start'])) {
    $_GET['start'] = date("Y-m-01");
}
if (!isset($_GET['end'])) {
    $_GET['end'] = date("Y-m-d", strtotime('+1 month'));
}

$optParams = array(
  'orderBy' => 'startTime',
  'singleEvents' => true,
  'timeMin' =>  date('c', strtotime($_GET['start'])),
  'timeMax' =>  date('c', strtotime($_GET['end']))
);

/**
 * Merges a calendar into the main event array
 *
 * @param Google_Service_Calendar $cxn_gcal   The Google Calendar connection
 * @param array                   $optParams  The parameters to pass to the API
 * @param string                  $cal_id     The ID of the calendar
 * @param array                   $calendar   The calendar details
 * @param array                   $all_events The array of all events
 *
 * @return void
 */
function mergeCalendar($cxn_gcal, $optParams, $cal_id, $calendar, &$all_events)
{
    /* calendar = array(3) {
    ["name"]=>
    string(8) "Holidays"
    ["src"]=>
    string(59) "k6ihf65p5md3okg9fpu4r2q36qk80r7e@import.calendar.google.com"
    ["color"]=>
    string(7) "#865A5A"
    }*/

    $results = $cxn_gcal->events->listEvents($calendar['src'], $optParams);
    $events = $results->getItems();

    foreach ($events as $event) {
        $start = $event->start->dateTime 
            ? $event->start->dateTime 
            : $event->start->date;

        $end = $event->end->dateTime 
            ? $event->end->dateTime 
            : $event->end->date;

        $declined = false;
        foreach ($event->attendees as $attendee) {
            if ($attendee->email == $calendar['src'] && $attendee->responseStatus == "declined") {
                $declined = true;
                continue;
            }
        }
        $summary = $event->summary;
        if ($declined) {
            $summary = "<strike>".$summary."</strike>";
        }
        $clean_summary = removeEmoji($summary);
        $clean_summary = trim($clean_summary);

        $event_id = sha1($start.$end.$clean_summary);

        if (isset($all_events[$event_id])) {
            $all_events[$event_id]['calendars'][] = $cal_id;
        } else {
            $margin = $background = $calendar['color'];

            if (!$clean_summary) {
                // print("THEME: " . THEME);
                $colour = THEME == "nighttime" ? '#000' : '#FFF';
                $margin = $background = $colour;
            }
            $all_events[$event_id] = array(
            "allDay" => $event->start->date ? true : false,
            "title"  => $summary,
            "first"  => $calendar['src'],
            "clean"  => $clean_summary,
            "cleancount"  => bin2hex($clean_summary),
            "id"     => $event->id,
            "end"    => $end,
            "start"  => $start,
            "calendars" => array($cal_id),
            "backgroundColor" => $margin,
            "borderColor" => $background,
            "full_event" => array($event)
            );
        }
    }
}
// efb88f
// 3f

$all_events = array();


foreach ($google_calendars as $cal_id => $calendar) {
    mergeCalendar($cxn_gcal, $optParams, $cal_id, $calendar, $all_events);
}

$events_out = array();

foreach ($all_events as $id => &$event) {
    if (count($event['calendars']) > 1) {
        sort($event['calendars']);

        $merged = implode("-", $event['calendars']);

        if (isset($merged_calendars[$merged])) {
            $event['backgroundColor'] = $merged_calendars[$merged]['color'];
        } else {
            $event['backgroundColor'] = '#AAA';
        }

        $event['borderColor'] = adjustBrightness($event['backgroundColor'], -25);

        $bullets = '';
        foreach ($event['calendars'] as $cal_id) {
            $bullets .= $google_calendars[$cal_id]['emoji'];
        }
        //$event['title'] = $bullets.' '.$event['title'];
        // Stuff
    }
    $events_out[] = $event;
}

header('content-type: application/json; charset: utf-8');

echo json_encode($events_out);
