<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/radiator.lib.php';

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment(
    $loader,
    [
        'cache' => './cache'
    ]
);

$template = $twig->load('index.html.twig');

$view = [
    'ical_calendars'   => $ical_calendars,
    'google_calendars' => $google_calendars,
    'merged_calendars' => $merged_calendars,
    'theme' => THEME,
    'calendar_set' => CALENDAR_SET
];


echo $template->render($view);
