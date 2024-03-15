<?php

header("Content-Type: application/javascript");

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

$template = $twig->load('radiator.js.twig');

$view = [
    'ical_calendars'   => $ical_calendars,
    'google_calendars' => $google_calendars,
    'merged_calendars' => $merged_calendars,
    'theme' => THEME,
    'calendar_set' => CALENDAR_SET,
    'mapbox_token' => MAPBOX_API_TOKEN
];

if (DEV_MODE) {
    $view['git_branch'] = git_branch();
}

if (THEME == "nighttime") {
    $view['mapbox_url'] = "mapbox://styles/aquarion/cj656i7c261pn2rolp2i4ptsh";
} else {
    $view['mapbox_url'] = "mapbox://styles/mapbox/streets-v11";
}

echo $template->render($view);
