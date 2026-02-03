<?php

namespace App\Console\Commands;

use App\Services\GoogleAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GoogleAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:auth {account : The account name to authenticate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Authenticate a Google Calendar account via OAuth';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $account = $this->argument('account');

        // Check if credentials are configured
        try {
            $googleAuth = app(GoogleAuthService::class);
        } catch (\App\Exceptions\InvalidCredentialsException $e) {
            $this->error('✗ Google API credentials not found!');
            $this->newLine();
            $this->line('To set up Google Calendar authentication:');
            $this->line('1. Create a Google Cloud project at: https://console.cloud.google.com');
            $this->line('2. Enable the Google Calendar API');
            $this->line('3. Create OAuth 2.0 credentials (Desktop application type)');
            $this->line('4. Download the credentials JSON file');
            $this->line('5. Save it to one of the following locations:');
            $this->line("   - Account-specific: storage/app/google/credentials_{$account}.json (recommended)");
            $this->line('   - Default: storage/app/google/credentials.json (used by all accounts)');
            $this->newLine();
            $this->line('Account-specific credentials allow different OAuth apps per account.');
            $this->line("For account '{$account}', looking for:");
            $this->line('  1. ' . storage_path("app/google/credentials_{$account}.json"));
            $this->line('  2. ' . storage_path('app/google/credentials.json') . ' (fallback)');
            $this->newLine();
            $this->line('For detailed instructions, see:');
            $this->info('https://developers.google.com/calendar/api/quickstart/php');

            return Command::FAILURE;
        }

        // Check if account already has a valid token
        if ($googleAuth->hasValidToken($account)) {
            $this->info("✓ Account '{$account}' already has a valid token.");

            // Show which credentials file is being used
            $disk = Storage::disk('local');
            $accountSpecificPath = "google/credentials_{$account}.json";
            $defaultPath = 'google/credentials.json';
            $usingPath = $disk->exists($accountSpecificPath) ? $accountSpecificPath : $defaultPath;
            $this->line('Using credentials: ' . basename($usingPath));

            $this->line('Testing with calendar API...');

            try {
                $service = $googleAuth->getCalendarService($account);
                $results = $service->events->listEvents('primary', [
                    'maxResults' => 3,
                    'orderBy' => 'startTime',
                    'singleEvents' => true,
                    'timeMin' => date('c'),
                ]);

                $this->info('✓ Successfully fetched calendar events!');
                $this->newLine();
                $this->line('Upcoming events:');
                foreach ($results->getItems() as $event) {
                    $start = $event->start->dateTime;
                    if (empty($start)) {
                        $start = $event->start->date;
                    }
                    $this->line("  - {$event->getSummary()} ({$start})");
                }

                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error("✗ Error testing calendar API: {$e->getMessage()}");

                return Command::FAILURE;
            }
        }

        // Need to authorize - get the auth URL
        // Show which credentials file is being used
        $disk = Storage::disk('local');
        $accountSpecificPath = "google/credentials_{$account}.json";
        $defaultPath = 'google/credentials.json';
        $usingPath = $disk->exists($accountSpecificPath) ? $accountSpecificPath : $defaultPath;

        $authUrl = $googleAuth->getAuthorizationUrl($account);

        $this->warn("Account '{$account}' needs authorization.");
        $this->line('Using credentials: ' . basename($usingPath));
        $this->newLine();
        $this->line('Open the following link in your browser:');
        $this->info($authUrl);
        $this->newLine();
        $this->line('After authorizing, you\'ll be redirected to a page with a code.');
        $this->line("The token will be automatically saved for account: {$account}");
        $this->newLine();

        $openBrowser = $this->confirm('Open URL in browser?', true);

        if ($openBrowser) {
            $this->openUrlInBrowser($authUrl);

            // Poll for token to be saved by web callback
            $this->line('Waiting for authorization (or enter code manually)...');
            $maxWaitTime = 120; // 2 minutes
            $checkInterval = 2; // Check every 2 seconds
            $waited = 0;

            while ($waited < $maxWaitTime) {
                if ($googleAuth->hasValidToken($account)) {
                    $this->info("✓ Token received and saved automatically!");

                    // Test the token
                    $this->line('Testing with calendar API...');
                    $service = $googleAuth->getCalendarService($account);
                    $results = $service->events->listEvents('primary', [
                        'maxResults' => 3,
                        'orderBy' => 'startTime',
                        'singleEvents' => true,
                        'timeMin' => date('c'),
                    ]);

                    $this->info('✓ Successfully fetched calendar events!');
                    $this->newLine();
                    $this->line('Upcoming events:');
                    foreach ($results->getItems() as $event) {
                        $start = $event->start->dateTime;
                        if (empty($start)) {
                            $start = $event->start->date;
                        }
                        $this->line("  - {$event->getSummary()} ({$start})");
                    }

                    return Command::SUCCESS;
                }

                sleep($checkInterval);
                $waited += $checkInterval;
                $this->output->write('.');
            }

            $this->newLine();
            $this->warn('Timeout waiting for web authorization. You can enter the code manually below.');
        }

        $authCode = $this->ask('Enter authorization code (or press Ctrl+C to cancel)');

        if (empty($authCode)) {
            $this->error('No authorization code provided.');

            return Command::FAILURE;
        }

        // Exchange code for token
        try {
            $googleAuth->fetchAccessToken($authCode, $account);
            $this->info("✓ Token saved successfully for account: {$account}");

            // Test it
            $this->line('Testing with calendar API...');
            $service = $googleAuth->getCalendarService($account);
            $results = $service->events->listEvents('primary', [
                'maxResults' => 3,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => date('c'),
            ]);

            $this->info('✓ Successfully fetched calendar events!');
            $this->newLine();
            $this->line('Upcoming events:');
            foreach ($results->getItems() as $event) {
                $start = $event->start->dateTime;
                if (empty($start)) {
                    $start = $event->start->date;
                }
                $this->line("  - {$event->getSummary()} ({$start})");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Open URL in default browser
     */
    protected function openUrlInBrowser(string $url): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => "open '{$url}'",
            'Linux' => "xdg-open '{$url}'",
            'Windows' => "start '{$url}'",
            default => null,
        };

        if ($command) {
            exec($command);
            $this->line('Browser opened...');
        }
    }
}
