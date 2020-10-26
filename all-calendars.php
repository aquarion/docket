<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/gcal.lib.php';
require __DIR__ . '/lib/radiator.lib.php';
include("calendars.inc.php");

define('SEND_JSON_ERRORS', True);

// Get the API client and construct the service object.
$client = getClient();
$cxn_gcal = new Google_Service_Calendar($client);

// Print the next events on the user's calendar.

if(!isset($_GET['start'])){
  $_GET['start'] = date("Y-m-01");
}
if(!isset($_GET['end'])){
  $_GET['end'] = date("Y-m-30");
}

$optParams = array(
  'orderBy' => 'startTime',
  'singleEvents' => true,
  'timeMin' =>  date('c', strtotime($_GET['start'])),
  'timeMax' =>  date('c', strtotime($_GET['end']))
);

function merge_calendar($cxn_gcal, $optParams, $cal_id, $calendar, &$all_events){
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

  foreach($events as $event){
    $start = $event->start->dateTime ? $event->start->dateTime : $event->start->date;
    $end = $event->end->dateTime ? $event->end->dateTime : $event->end->date;

    $clean_summary = removeEmoji($event->summary);
    $clean_summary = trim($clean_summary);

    $event_id = sha1($start.$end.$clean_summary);

    if(isset($all_events[$event_id])){
      $all_events[$event_id]['calendars'][] = $cal_id;

    } else {
      $margin = $background = $calendar['color'];

      if(!$clean_summary){
        // print("THEME: " . THEME);
        $colour = THEME == "nighttime" ? '#000' : '#FFF';
        $margin = $background = $colour;
      }
      $all_events[$event_id] = array(
          "allDay" => $event->start->date ? true : false,
          "title"  => $event->summary,
          "first"  => $calendar['src'],
          "clean"  => $clean_summary,
          "cleancount"  => bin2hex($clean_summary),
          "id"     => $event->id,
          "end"    => $end,
          "start"  => $start,
          "calendars" => array($cal_id),
          "backgroundColor" => $margin,
          "borderColor" => $background
      );
    }
  }


}
#efb88f
#3f

$all_events = array();


foreach($calendars as $cal_id => $calendar){
  merge_calendar($cxn_gcal, $optParams, $cal_id, $calendar, $all_events);
}

$events_out = array();

foreach($all_events as $id => &$event){
  if(count($event['calendars']) > 1){
    $event['backgroundColor'] = '#AAA';

    $bullets = '';
    foreach($event['calendars'] as $cal_id){
      $bullets .= $calendars[$cal_id]['emoji'];
    }
    //$event['title'] = $bullets.' '.$event['title'];
    // Stuff
  }
  $events_out[] = $event;
}

header('content-type: application/json; charset: utf-8');

echo json_encode($events_out);