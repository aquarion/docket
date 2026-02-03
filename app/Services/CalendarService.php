<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalendarService
{
    /**
     * Load calendar configuration from Laravel config
     */
    public function loadCalendarConfig(): array
    {
        return [
            'ical_calendars' => config('calendars.ical_calendars', []),
            'google_calendars' => config('calendars.google_calendars', []),
            'merged_calendars' => config('calendars.merged_calendars', []),
        ];
    }

    /**
     * Filter calendars based on calendar set configuration
     */
    public function filterBySet(array $calendarConfig, string $setId): array
    {
        $cacheKey = "calendars:filtered:{$setId}";
        $cacheTTL = config('services.calendar.cache_ttl', 15 * 60);

        // Try to get from cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $calendarSets = config('calendars.calendar_sets', []);

        // If set doesn't exist or no filtering needed, return all calendars
        if (! isset($calendarSets[$setId])) {
            $result = $calendarConfig;
            $cacheResult = Cache::put($cacheKey, $result, $cacheTTL);
            if (! $cacheResult) {
                Log::warning('Failed to cache calendar result', ['setId' => $setId]);
            }
            return $result;
        }

        $setConfig = $calendarSets[$setId];
        $allowedCalendars = $setConfig['calendars'] ?? [];

        // If '*' is specified, include all calendars
        if (in_array('*', $allowedCalendars)) {
            $cacheResult = Cache::put($cacheKey, $calendarConfig, $cacheTTL);
            if (! $cacheResult) {
                Log::warning('Failed to cache wildcard calendar result', ['setId' => $setId]);
            }
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

        $result = [
            'google_calendars' => $filteredGoogle,
            'ical_calendars' => $filteredIcal,
            'merged_calendars' => $filteredMerged,
        ];

        // Cache the filtered result for 15 minutes
        $cacheResult = Cache::put($cacheKey, $result, $cacheTTL);
        if (! $cacheResult) {
            Log::warning('Failed to cache filtered calendar result', ['setId' => $setId]);
        }

        return $result;
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

    /**
     * Clear all cached calendar filters
     */
    public function clearCache(): void
    {
        $availableSets = $this->getAvailableSetIds();
        foreach ($availableSets as $setId) {
            Cache::forget("calendars:filtered:{$setId}");
        }
    }
}
