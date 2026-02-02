<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.force_https', false) || ! config('app.debug')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $this->loadConfigOverrides();
    }

    /**
     * Load configuration overrides from storage/app/config/ if they exist.
     */
    protected function loadConfigOverrides(): void
    {
        $overrideDir = storage_path('app/config');

        if (! is_dir($overrideDir)) {
            return;
        }

        $configFiles = glob($overrideDir.'/*.php');

        foreach ($configFiles as $file) {
            $configName = basename($file, '.php');
            $overrideConfig = require $file;

            if (is_array($overrideConfig)) {
                config([
                    $configName => array_replace_recursive(
                        config($configName, []),
                        $overrideConfig
                    ),
                ]);
            }
        }
    }
}
