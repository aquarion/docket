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
    $this->loadConfigOverrides();
  }

  /**
   * Load configuration overrides from etc/config/ if they exist.
   */
  protected function loadConfigOverrides(): void
  {
    $overrideDir = base_path('etc/config');

    if (! is_dir($overrideDir)) {
      return;
    }

    $configFiles = glob($overrideDir . '/*.php');

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
