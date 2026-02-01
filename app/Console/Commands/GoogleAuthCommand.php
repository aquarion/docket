<?php

namespace App\Console\Commands;

use App\Services\GoogleAuthService;
use Illuminate\Console\Command;

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
    public function handle(GoogleAuthService $googleAuth): int
    {
        $account = $this->argument('account');

        // Check if account already has a valid token
        if ($googleAuth->hasValidToken($account)) {
            $this->info("✓ Account '{$account}' already has a valid token.");
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
        $authUrl = $googleAuth->getAuthorizationUrl($account);

        $this->warn("Account '{$account}' needs authorization.");
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
        }

        $authCode = $this->ask('Enter authorization code');

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
