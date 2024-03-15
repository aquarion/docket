<?php

header("Content-Type: text/css");

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/radiator.lib.php';


$twig_config = [];

if (!DEV_MODE) {
    $twig_config['cache']  = './cache';
}


$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment(
    $loader,
    $twig_config
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
