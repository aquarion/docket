<?php

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

$template = $twig->load('index.html.twig');

$view = [
    'ical_calendars'   => $ical_calendars,
    'google_calendars' => $google_calendars,
    'merged_calendars' => $merged_calendars,
    'theme' => THEME,
    'calendar_set' => CALENDAR_SET
];

if (DEV_MODE) {
    $view['git_branch'] = git_branch();
}

echo $template->render($view);
