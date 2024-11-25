<?php

header("Content-Type: text/css");

define('HOME_DIR', __DIR__ . '/..');

require HOME_DIR . '/vendor/autoload.php';
require HOME_DIR . '/lib/radiator.lib.php';

$twig_config = [];

if (!DEV_MODE) {
    $twig_config['cache']  = HOME_DIR . '/cache';
}


$loader = new \Twig\Loader\FilesystemLoader(HOME_DIR . '/templates');
$twig = new \Twig\Environment(
    $loader,
    $twig_config
);

$all_calendars = array_merge($ical_calendars, $google_calendars);

foreach ($all_calendars as &$cal) {
    $cal['color_dim'] = RGBToCSS($cal['color'], .5);
}

$template = $twig->load('calendars.css.twig');

$view = [
    'ical_calendars'   => $ical_calendars,
    'google_calendars' => $google_calendars,
    'all_calendars' => $all_calendars,
    'theme' => THEME,
    'calendar_set' => CALENDAR_SET,
    'mapbox_token' => MAPBOX_API_TOKEN
];

echo $template->render($view);
