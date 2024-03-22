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

header("Content-Type: application/javascript");

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

$template = $twig->load('radiator.js.twig');

$view = [
    'ical_calendars'   => $ical_calendars,
    'google_calendars' => $google_calendars,
    'merged_calendars' => $merged_calendars,
    'theme' => THEME,
    'calendar_set' => CALENDAR_SET,
    'mapbox_token' => MAPBOX_API_TOKEN,
    'latitude' => MY_LAT,
    'longitude' => MY_LON
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
