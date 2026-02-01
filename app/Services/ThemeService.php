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
     * Get current festival based on date, with debug override support
     *
     * In debug mode, allows overriding the festival via query parameter:
     * ?festival=easter or ?festival=christmas or ?festival=none
     */
    public function getFestival(): ?string
    {
        $festivals = config('festivals.festivals', []);

        // Allow debug mode to override festival via query parameter
        if (config('festivals.debug_override_enabled')) {
            $requestFestival = request()->query('festival');
            if ($requestFestival === 'none') {
                return null;
            }
            if ($requestFestival && array_key_exists($requestFestival, $festivals)) {
                return $requestFestival;
            }
        }

        // Check each festival to see if it's currently active
        foreach ($festivals as $key => $festival) {
            if (isset($festival['callback']) && is_callable($festival['callback'])) {
                if ($festival['callback']()) {
                    return $key;
                }
            }
        }

        return null;
    }
}
