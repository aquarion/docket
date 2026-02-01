<?php

namespace App\Services;

class ThemeService
{
  /**
   * Get current theme based on time of day
   */
  public function getTheme(): string
  {
    $lat = config('services.location.latitude', 51.5074);
    $lon = config('services.location.longitude', -0.1278);

    $sunInfo = date_sun_info(time(), $lat, $lon);
    $sunset = $sunInfo['sunset'];
    $sunrise = $sunInfo['sunrise'];

    return (time() > $sunset || time() < $sunrise) ? 'nighttime' : 'daytime';
  }

  /**
   * Get current festival based on date
   */
  public function getFestival(): ?string
  {
    return ((int) date('m')) === 12 ? 'christmas' : null;
  }
}
