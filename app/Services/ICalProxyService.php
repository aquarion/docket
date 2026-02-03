<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ICalProxyService
{
    /**
     * Fetch an iCal calendar with optional caching
     */
    public function fetchCalendar(string $calendarId, bool $raw = false, bool $useCache = true): array
    {
        $calendarService = app(CalendarService::class);
        $config = $calendarService->loadCalendarConfig();

        if (! isset($config['ical_calendars'][$calendarId])) {
            throw new \Exception("Calendar not found: {$calendarId}");
        }

        $calendar = $config['ical_calendars'][$calendarId];
        $cacheKey = "icalproxy.{$calendarId}";

        // Try cache first if enabled
        if ($useCache && Cache::has($cacheKey)) {
            return [
                'content' => Cache::get($cacheKey),
                'content_type' => 'text/calendar; charset=utf-8',
                'cached' => true,
            ];
        }

        // Fetch from source
        $client = new Client;
        $response = $client->request('GET', $calendar['src']);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Error accessing calendar: {$calendarId}");
        }

        $content = $response->getBody()->getContents();
        $contentType = $response->hasHeader('content-type')
            ? $response->getHeader('content-type')[0]
            : 'text/calendar; charset=utf-8';

        // Cache with configurable TTL (via services.calendar.cache_ttl)
        if ($useCache) {
            $cacheTtlMinutes = config('services.calendar.cache_ttl', 15 * 60) / 60;
            $cacheResult = Cache::put($cacheKey, $content, now()->addMinutes($cacheTtlMinutes));
            if (! $cacheResult) {
                Log::warning('Failed to cache iCal content', ['calendar_id' => $calendarId, 'url' => $calendar['src']]);
            }
        }

        return [
            'content' => $content,
            'content_type' => $raw ? 'text/plain; charset=utf-8' : $contentType,
            'cached' => false,
        ];
    }
}
