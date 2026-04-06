<?php

namespace Tests;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    protected static bool $hotFileExisted = false;

    public static function runningInContinuousIntegration(): bool
    {
        return env('CI', false);
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (static::runningInSail()) {
            // Move Vite hot file to force use of built assets
            $hotFile = getcwd().'/public/hot';
            $hotFileBackup = getcwd().'/public/hot.backup';
            if (file_exists($hotFile)) {
                static::$hotFileExisted = true;
                rename($hotFile, $hotFileBackup);
            }
            // we should be in a Sail environment, so connect to the Selenium container
        } elseif (static::runningInContinuousIntegration()) {
            // we are in CI environment, so connect to the Selenium container
        } else {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Clean up after Dusk test execution.
     */
    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function cleanup(): void
    {
        if (static::runningInSail() && static::$hotFileExisted) {
            // Restore Vite hot file if it existed before tests
            $hotFile = getcwd().'/public/hot';
            $hotFileBackup = getcwd().'/public/hot.backup';
            if (file_exists($hotFileBackup)) {
                rename($hotFileBackup, $hotFile);
            }
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL');

        if (static::runningInSail() && is_string($driverUrl) && str_contains($driverUrl, 'localhost')) {
            $driverUrl = str_replace('localhost', 'selenium', $driverUrl);
        }

        return RemoteWebDriver::create(
            $driverUrl ?? 'http://selenium:4444',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Get the base URL for browser visits.
     */
    protected function baseUrl(): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if (static::runningInSail() && str_contains($appUrl, 'localhost')) {
            return 'http://host.docker.internal';
        }

        return $appUrl;
    }

    /**
     * Browse as an authenticated user for routes protected by auth middleware.
     */
    protected function browseAsAuthenticatedUser(callable $callback): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($callback, $user): void {
            $callback($browser->loginAs($user));
        });
    }
}
