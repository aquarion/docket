<?php

namespace App\Exceptions;

class InvalidCredentialsException extends \Exception
{
    public function __construct(string $path)
    {
        $message = "Google API credentials not found at: {$path}\n\n";
        $message .= "To set up Google Calendar authentication:\n";
        $message .= "1. Visit https://console.cloud.google.com\n";
        $message .= "2. Create a project and enable the Google Calendar API\n";
        $message .= "3. Create OAuth 2.0 credentials (Desktop application)\n";
        $message .= "4. Download credentials.json and save to: {$path}\n";
        $message .= "\nFor detailed instructions: https://developers.google.com/calendar/api/quickstart/php";

        parent::__construct($message);
    }
}
