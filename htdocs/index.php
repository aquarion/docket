<?php

define('HOME_DIR', __DIR__.'/..');

require HOME_DIR . '/vendor/autoload.php';
require HOME_DIR . '/lib/radiator.lib.php';

$twig_config = [];

if (!DEV_MODE) {
    $twig_config['cache']  = HOME_DIR.'/cache';
}


$loader = new \Twig\Loader\FilesystemLoader(HOME_DIR.'/templates');
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
