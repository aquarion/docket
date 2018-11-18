<?php

function fullcal_json($calendars){
    $output = [];
    foreach($calendars as $id => $calendar){
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

register_shutdown_function( "check_for_fatal" );
function log_error( $num, $str, $file, $line, $context = null )
{
    log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
}
function check_for_fatal()
{
    $error = error_get_last();
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