<?php

// Simple test script to debug the issue
$calendarSet = $_GET['calendar_set'] ?? 'NOT_PROVIDED';
$url = 'http://127.0.0.1:8000/all-calendars?end=2026-03-01&calendar_set='.urlencode($calendarSet);

echo "Testing URL: $url\n\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json',
            'User-Agent: Test-Script/1.0',
        ],
        'timeout' => 10,
    ],
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Error: Failed to fetch data\n";
    $error = error_get_last();
    echo 'Last error: '.print_r($error, true)."\n";
} else {
    echo 'Response length: '.strlen($response)."\n";
    echo 'First 500 chars: '.substr($response, 0, 500)."\n\n";

    $data = json_decode($response, true);
    if ($data) {
        echo "JSON decode successful\n";
        echo 'Keys in response: '.implode(', ', array_keys($data))."\n";

        if (isset($data['json_cals'])) {
            echo 'Calendar count: '.count($data['json_cals'])."\n";
            echo 'Calendar names: '.implode(', ', array_keys($data['json_cals']))."\n";
        }
    } else {
        echo "JSON decode failed\n";
        echo "Raw response:\n".$response."\n";
    }
}
