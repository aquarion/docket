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

define('HOME_DIR', __DIR__.'/..');

require HOME_DIR . '/vendor/autoload.php';
require HOME_DIR . '/lib/radiator.lib.php';

$twig_config = [];

if (!DEV_MODE) {
    $twig_config['cache']  = HOME_DIR.'/cache';
}

if (isset($_GET['clear_cache'])) {
    $files = clearCacheFiles(HOME_DIR.'/cache');
    echo "Cache cleared";
    echo "<pre>";
    echo implode("\n", $files);
    echo "</pre>";
    die();
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
    $view['git_branch'] = gitBranch();
}

echo $template->render($view);
