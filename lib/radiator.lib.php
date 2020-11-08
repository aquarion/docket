<?php

require __DIR__ . '/../calendars.inc.php';

$set = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, MY_LAT, MY_LON);
$rise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, MY_LAT, MY_LON);

if(time() > $set || time() < $rise ){
	define("THEME", "nighttime");
} else {
	define("THEME", "daytime");
}

function fullcal_json($google_calendars){
    $output = [];
    foreach($google_calendars as $id => $calendar){
        $line = array(
            'url' => 'calendar.php?cal='.$calendar['src'],
            'className' => 'cal-'.$id,
        );
        if(isset($calendar['rendering'])){
            $line['rendering'] = $calendar['rendering'];
            $line['backgroundColor'] = $calendar['color'];
        }
        $output[] = $line;
    }
    return json_encode($output, JSON_PRETTY_PRINT);
}


function checkEmoji($str)
{
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    preg_match($regexEmoticons, $str, $matches_emo);
    if (!empty($matches_emo[0])) {
        return false;
    }

    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    preg_match($regexSymbols, $str, $matches_sym);
    if (!empty($matches_sym[0])) {
        return false;
    }

    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    preg_match($regexTransport, $str, $matches_trans);
    if (!empty($matches_trans[0])) {
        return false;
    }

    // Match Miscellaneous Symbols
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    preg_match($regexMisc, $str, $matches_misc);
    if (!empty($matches_misc[0])) {
        return false;
    }

    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    preg_match($regexDingbats, $str, $matches_bats);
    if (!empty($matches_bats[0])) {
        return false;
    }

    return true;
}

function removeEmoji($text) {

    return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);

}

register_shutdown_function( "check_for_fatal" );
function log_error( $num, $str, $file, $line, $context = null )
{
    log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
}
function check_for_fatal()
{
    $error = error_get_last();
    if (! $error ){
	return;
    }
    if ( $error["type"] == E_ERROR )
        log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
}
function log_exception($e){
    if (!is_a($e, 'Exception')){
        var_dump($e);
        debug_print_backtrace();
        die();
    }
    if(defined('SEND_JSON_ERRORS') && SEND_JSON_ERRORS == true ){
        send_json_error($e->getCode(), get_class($e).": ".$e->getMessage(), $e->getFile(), $e->getLine(), $e);
    } elseif(defined('SEND_TEXT_ERRORS') && SEND_TEXT_ERRORS == true ){
        send_text_error($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    } else {
        $file = str_replace(realpath(getcwd().DIRECTORY_SEPARATOR.'..'), '', $e->getFile());
        header('HTTP/1.1 500 Something Bad');
        header('content-type: text/html; charset: utf-8');
        print "<div style='text-align: center;'>";
        print "<h2 style='color: rgb(190, 50, 50);'>Exception Occured:</h2>";
        print "<table style='width: 800px; display: inline-block;'>";
        print "<tr style='background-color:rgb(230,230,230);'><th style='width: 80px;'>Type</th><td>" . get_class( $e ) . "</td></tr>";
        print "<tr style='background-color:rgb(240,240,240);'><th>Message</th><td>{$e->getMessage()}</td></tr>";
        print "<tr style='background-color:rgb(230,230,230);'><th>File</th><td>{$file}</td></tr>";
        print "<tr style='background-color:rgb(240,240,240);'><th>Line</th><td>{$e->getLine()}</td></tr>";
        print "</table></div>";
        die();
    }
}
function send_json_error($errno, $errstr, $errfile, $errline, $e = false){
    $file = str_replace(realpath(getcwd().DIRECTORY_SEPARATOR.'..'), '', $errfile);
    header('HTTP/1.1 500 Something Bad');
    header('content-type: application/json; charset: utf-8');
    $backtrace = debug_backtrace();
    echo json_encode(array('response' => 500, 'message' => $errstr, 'file' => $file, 'line' => $errline, 'backtrace' => $e->getTrace() ));

    error_log("JSON Error: [{$_SERVER['HTTP_HOST']}] {$errstr} in {$errfile}:{$errline}");

    die();
    // throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
function send_text_error($errno, $errstr, $errfile, $errline ){
    $file = str_replace(realpath(getcwd().DIRECTORY_SEPARATOR.'..'), '', $errfile);
    print "{$errstr} in {$file} at {$errline}";
        debug_print_backtrace();
    die();
    // throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler('log_error');
set_exception_handler( "log_exception" );


