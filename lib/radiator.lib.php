<?php
/**
 * File: Radiator.inc.php
 * php version 7.2
 *
 * Library file for Docket
 *
 * @category Library
 * @package  Docket
 * @author   "Nicholas Avenell" <nicholas@istic.net>
 * @license  BSD-3-Clause https://opensource.org/license/bsd-3-clause
 * @link     https://docket.hubris.house
 */

// Set the calendar version based on the 'version' GET parameter
if (isset($_GET['version']) && $_GET['version'] == "work") {
    define("CALENDAR_SET", "work");
} else {
    define("CALENDAR_SET", "home");
}

if (file_exists(__DIR__ . '/../calendars.inc.php')) {
    include __DIR__ . '/../calendars.inc.php';
} else {
    throw new Exception("Config file not found");
}

// Get sunrise and sunset times for the current location
$sun_info = date_sun_info(time(), MY_LAT, MY_LON);

$set = $sun_info['sunset'];
$rise = $sun_info['sunrise'];
// Set the theme based on whether it's currently daytime or nighttime

if (time() > $set || time() < $rise) {
    define("THEME", "nighttime");
} else {
    define("THEME", "daytime");
}

/**
 * Adjusts the brightness of a color.
 * Negative values make the color darker, positive values make the color lighter.
 *
 * @param string $hex   The hex color to adjust.
 * @param int    $steps The number of steps to adjust the color by. 
 *                      Should be between -255 and 255.
 * 
 * @return string The adjusted hex color.
 */
function adjustBrightness($hex, $steps)
{
    // From https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php

    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . 
               str_repeat(substr($hex, 1, 1), 2) . 
               str_repeat(substr($hex, 2, 1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0, min(255, $color + $steps)); // Adjust color
        // Make two char hex code
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); 
    }

    return $return;
}

/**
 * Converts a hexadecimal color code to RGBA format.
 *
 * @param string $hex The hexadecimal color code to convert.
 * 
 * @return string The RGBA color code.
 */
function hexToRGBA($hex)
{
    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . 
               str_repeat(substr($hex, 1, 1), 2) . 
               str_repeat(substr($hex, 2, 1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);

    $color_rgb = [];

    foreach ($color_parts as $i => $color) {
        $color_rgb[] = hexdec($color); // Convert to decimal;
    }

    return $color_rgb;
}

/**
 * Converts an RGB color value to a CSS color string.
 *
 * @param array $color The RGB color value as an (red, green, and blue) array
 * @param float $alpha The alpha value (opacity) of the color, ranging from 0 to 1.
 * 
 * @return string The CSS color string representing the RGB color value.
 */
function RGBToCSS($color, $alpha)
{
    $color_rgb = hexToRGBA($color);
    return "rgba({$color_rgb[0]}, {$color_rgb[1]}, {$color_rgb[2]}, {$alpha})";
}

/**
 * Retrieves the current Git branch.
 *
 * @return string The name of the current Git branch.
 */
function gitBranch()
{

    $stringfromfile = file(__DIR__.'/../.git/HEAD', FILE_USE_INCLUDE_PATH);

    //get the string from the array
    $firstLine = $stringfromfile[0]; 

    //seperate out by the "/" in the string
    $explodedstring = explode("/", $firstLine, 3); 

    //get the one that is always the branch name
    return $explodedstring[2]; 

}


/**
 * Removes emojis from the given text.
 *
 * @param string $text The text from which emojis should be removed.
 * 
 * @return string The text without emojis.
 */
function removeEmoji($text)
{
    $preg = '/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|'.
            '\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|'.
            '\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}]'.
            '[\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}]'.
            '[\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|'.
            '[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}]'.
            '[\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]'.
            '?/u';
    return preg_replace($preg, '', $text);
}

/**
 * Logs an error message.
 *
 * @param int    $num     The error number.
 * @param string $str     The error message.
 * @param string $file    The file where the error occurred.
 * @param int    $line    The line number where the error occurred.
 * @param mixed  $context (optional) Additional context information. Default is null.
 * 
 * @return void
 */
function logError($num, $str, $file, $line, $context = null)
{
    logException(new ErrorException($str, 0, $num, $file, $line));
}
set_error_handler('logError');


/**
 * Checks for fatal errors.
 *
 * This function is responsible for checking if any fatal errors have occurred.
 * It performs necessary actions to handle fatal errors 
 * and prevent script termination.
 *
 * @return void
 */
function checkForFatal()
{
    $error = error_get_last();
    if (! $error) {
        return;
    }
    if ($error["type"] == E_ERROR) {
        logError($error["type"], $error["message"], $error["file"], $error["line"]);
    }
}
register_shutdown_function("checkForFatal");

/**
 * Logs an exception.
 *
 * @param Exception $e The exception to be logged.
 * 
 * @return void
 */
function logException($e)
{
    if (!is_a($e, 'Exception')) {
        var_dump($e);
        debug_print_backtrace();
        die();
    }
    if (defined('SEND_JSON_ERRORS') && SEND_JSON_ERRORS == true) {
        $location = get_class($e) . ": " . $e->getMessage();
        sendJsonError($e->getCode(), $location, $e->getFile(), $e->getLine(), $e);
    } elseif (defined('SEND_TEXT_ERRORS') && SEND_TEXT_ERRORS == true) {
        sendTextError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    } else {
        $basedir = realpath(getcwd() . DIRECTORY_SEPARATOR . '..');
        $file = str_replace($basedir, '', $e->getFile());
        header('HTTP/1.1 500 Something Bad');
        header('content-type: text/html; charset: utf-8');
        print "<div style='text-align: center;'>";
        print "<h2 style='color: rgb(190, 50, 50);'>Exception Occured:</h2>";
        print "<table style='width: 800px; display: inline-block;'>";
        print "<tr style='background-color:rgb(230,230,230);'>";
        print "<th style='width: 80px;'>Type</th><td>" . get_class($e) 
            . "</td></tr>";
        print "<tr style='background-color:rgb(240,240,240);'>";
        print "<th>Message</th><td>{$e->getMessage()}</td></tr>";
        print "<tr style='background-color:rgb(230,230,230);'>";
        print "<th>File</th><td>{$file}</td></tr>";
        print "<tr style='background-color:rgb(240,240,240);'>";
        print "<th>Line</th><td>{$e->getLine()}</td></tr>";
        print "</table></div>";
        die();
    }
}
set_exception_handler("logException");

/**
 * Sends a JSON error response.
 *
 * @param int    $errno   The error number.
 * @param string $errstr  The error message.
 * @param string $errfile The file where the error occurred.
 * @param int    $errline The line number where the error occurred.
 * @param mixed  $e       The exception that occurred. Default is false.
 * 
 * @return void
 */
function sendJsonError($errno, $errstr, $errfile, $errline, $e = false)
{
    $basedir = realpath(getcwd() . DIRECTORY_SEPARATOR . '..');
    $file = str_replace($basedir, '', $e->getFile());
    header('HTTP/1.1 500 Something Bad');
    header('content-type: application/json; charset: utf-8');
    $backtrace = debug_backtrace();
    echo json_encode(
        array(
        'response' => 500, 
        'message' => $errstr, 
        'file' => $file, 
        'line' => $errline, 
        'backtrace' => $e->getTrace() 
        )
    );

    error_log(
        "JSON Error: [{$_SERVER['HTTP_HOST']}] {$errstr}".
        " in {$errfile}:{$errline}"
    );

    die();
    // throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

/**
 * Sends a text error response.
 *
 * @param int    $errno   The error number.
 * @param string $errstr  The error message.
 * @param string $errfile The file where the error occurred.
 * @param int    $errline The line number where the error occurred.
 * 
 * @return void
 */
function sendTextError($errno, $errstr, $errfile, $errline)
{
    $basedir = realpath(getcwd() . DIRECTORY_SEPARATOR . '..');
    $file = str_replace($basedir, '', $errfile);
    print "{$errstr} in {$file} at {$errline}";
    debug_print_backtrace();
    die();
    // throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

/**
 * Clears the cache files in the given location.
 *
 * @param string $cacheLocation The location of the cache files.
 * 
 * @return array The list of files that were cleared.
 */
function clearCacheFiles($cacheLocation)
{
    $files = [];
    if (is_string($cacheLocation)) {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheLocation),
            \RecursiveIteratorIterator::LEAVES_ONLY
        ) as $file
        ) {
            if ($file->isFile() && ($file->getFilename() != '.gitkeep')) {
                $files[] = $file->getPathname();
                @unlink($file->getPathname());
            }
        }
    }
    return $files;
}

