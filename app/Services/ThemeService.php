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
            if ($this->isFestivalActive($festival)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Check if a festival is currently active based on its configuration
     */
    private function isFestivalActive(array $festival): bool
    {
        $type = $festival['type'] ?? null;

        switch ($type) {
            case 'easter_calculation':
                return $this->isEasterPeriodActive($festival);

            case 'month':
                $month = (int) date('m');
                return $month === ($festival['month'] ?? 0);

            default:
                // Legacy support for callback functions (if they exist)
                if (isset($festival['callback']) && is_callable($festival['callback'])) {
                    return $festival['callback']();
                }
                return false;
        }
    }

    /**
     * Check if Easter period is currently active
     */
    private function isEasterPeriodActive(array $festival): bool
    {
        $year = (int) date('Y');
        $easterSunday = easter_date($year);
        
        $daysBefore = $festival['days_before'] ?? 0;
        $daysAfter = $festival['days_after'] ?? 0;
        
        $startDate = strtotime("-{$daysBefore} days", $easterSunday);
        $endDate = strtotime("+{$daysAfter} days", $easterSunday);
        $today = strtotime('today');

        return $today >= $startDate && $today <= $endDate;
    }
}
