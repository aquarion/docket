<?php

namespace App\Services;

use Illuminate\Http\Request;

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
     * Get current festival based on date, with debug override support
     *
     * In debug mode, allows overriding the festival via query parameter:
     * ?festival=christmas or ?festival=none
     */
    public function getFestival(): ?string
    {
        // Allow debug mode to override festival via query parameter
        if (config('app.debug')) {
            $requestFestival = request()->query('festival');
            if ($requestFestival && in_array($requestFestival, ['christmas', 'none'])) {
                return $requestFestival === 'none' ? null : $requestFestival;
            }
        }

        // Default: Christmas in December
        return ((int) date('m')) === 12 ? 'christmas' : null;
    }
}
