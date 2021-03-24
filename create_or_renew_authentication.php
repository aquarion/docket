<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/gcal.lib.php';

use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}


// Get the API client and construct the service object.
$client = getClient();


$photosLibraryClient = new PhotosLibraryClient(['credentials' => getGoogleCreds()]);


$response = $photosLibraryClient->listMediaItems();
foreach ($response->iterateAllElements() as $item) {
    // Get some properties of a media item
    /* @var $item \Google\Photos\Library\V1\MediaItem */
    $id = $item->getId();
    $description = $item->getDescription();
    $mimeType = $item->getMimeType();
    $productUrl = $item->getProductUrl();
    $filename = $item->getFilename();
    print($filename.' '.$description."\n");

    break;
}

$service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => true,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);
$events = $results->getItems();

if (empty($events)) {
    print "No upcoming events found.\n";
} else {
    print "Upcoming events:\n";
    foreach ($events as $event) {
        $start = $event->start->dateTime;
        if (empty($start)) {
            $start = $event->start->date;
        }
        printf("%s (%s)\n", $event->getSummary(), $start);
    }
}