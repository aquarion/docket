<?php

namespace App\Services;

class CalendarService
{
    /**
     * Load calendar configuration from Laravel config
     */
    public function loadCalendarConfig(): array
    {
        // Check if legacy config should be used
        if (config('calendars.use_legacy_config')) {
            return $this->loadLegacyConfig();
        }

        return [
            'ical_calendars' => config('calendars.ical_calendars', []),
            'google_calendars' => config('calendars.google_calendars', []),
            'merged_calendars' => config('calendars.merged_calendars', []),
        ];
    }

    /**
     * Load legacy calendar configuration from calendars.inc.php
     */
    private function loadLegacyConfig(): array
    {
        $configFile = config('calendars.legacy_config_path', base_path('calendars.inc.php'));

        if (! file_exists($configFile)) {
            return [
                'ical_calendars' => [],
                'google_calendars' => [],
                'merged_calendars' => [],
            ];
        }

        // Isolate variables in closure to prevent scope pollution
        $config = (function () use ($configFile) {
            $ical_calendars = [];
            $google_calendars = [];
            $merged_calendars = [];

            include $configFile;

            return compact('ical_calendars', 'google_calendars', 'merged_calendars');
        })();

        return $config;
    }

    /**
     * Filter calendars based on calendar set configuration
     */
    public function filterBySet(array $calendarConfig, string $setId): array
    {
        $calendarSets = config('calendars.calendar_sets', []);

        // If set doesn't exist or no filtering needed, return all calendars
        if (! isset($calendarSets[$setId])) {
            return $calendarConfig;
        }

        $setConfig = $calendarSets[$setId];
        $allowedCalendars = $setConfig['calendars'] ?? [];

        // If '*' is specified, include all calendars
        if (in_array('*', $allowedCalendars)) {
            return $calendarConfig;
        }

        // Filter google_calendars
        $filteredGoogle = [];
        foreach ($calendarConfig['google_calendars'] as $id => $calendar) {
            if (in_array($id, $allowedCalendars)) {
                $filteredGoogle[$id] = $calendar;
            }
        }

        // Filter ical_calendars
        $filteredIcal = [];
        foreach ($calendarConfig['ical_calendars'] as $id => $calendar) {
            if (in_array($id, $allowedCalendars)) {
                $filteredIcal[$id] = $calendar;
            }
        }

        // Filter merged_calendars (only include if both calendars are in the set)
        $filteredMerged = [];
        foreach ($calendarConfig['merged_calendars'] as $mergeKey => $mergeConfig) {
            $calendarIds = explode('-', $mergeKey);
            $allIncluded = true;
            foreach ($calendarIds as $calId) {
                if (! in_array($calId, $allowedCalendars)) {
                    $allIncluded = false;
                    break;
                }
            }
            if ($allIncluded) {
                $filteredMerged[$mergeKey] = $mergeConfig;
            }
        }

        return [
            'google_calendars' => $filteredGoogle,
            'ical_calendars' => $filteredIcal,
            'merged_calendars' => $filteredMerged,
        ];
    }

    /**
     * Get available calendar set IDs
     */
    public function getAvailableSetIds(): array
    {
        return array_keys(config('calendars.calendar_sets', []));
    }

    /**
     * Get default calendar set ID
     */
    public function getDefaultSetId(): string
    {
        return config('calendars.default_calendar_set', 'all');
    }
}
