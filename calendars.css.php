<?php
header("Content-Type: text/css");

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/radiator.lib.php';

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment(
    $loader,
    [
        'cache' => './cache'
    ]
);

$template = $twig->load('calendars.css.twig');

$view = [
    'ical_calendars'   => $ical_calendars,
    'google_calendars' => $google_calendars,
    'all_calendars' => array_merge($ical_calendars, $google_calendars),
    'theme' => THEME,
    'calendar_set' => CALENDAR_SET,
    'mapbox_token' => MAPBOX_API_TOKEN
];

echo $template->render($view);
