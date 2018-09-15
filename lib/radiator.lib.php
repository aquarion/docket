<?php

function fullcal_json($calendars){
    $output = [];
    foreach($calendars as $id => $calendar){
        $line = array(
            'url' => 'calendar.php?cal='.$calendar['src'],
            'className' => 'cal-'.$id,
        );
        $output[] = $line;
    }
    return json_encode($output, JSON_PRETTY_PRINT);
}

