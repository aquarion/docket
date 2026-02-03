<?php

namespace App\Services;

use App\Support\ColorHelper;
use App\Support\StringHelper;
use DateTimeImmutable;
use DateTimeZone;
use Google_Service_Calendar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    public function __construct(
        private GoogleAuthService $googleAuth,
        private ThemeService $themeService
    ) {}

    /**
     * Fetch all events from all configured Google calendars
     */
    public function fetchAllCalendarEvents(array $googleCalendars, array $mergedCalendars, ?string $start = null, ?string $end = null, bool $debug = false): array
    {
        $start = $start ?? date('Y-m-01');
        $end = $end ?? date('Y-m-d', strtotime('+1 month'));

        // Create cache key based on calendars, date range, and merged config
        $cacheKey = 'google_calendar_events:' . md5(serialize([
            'calendars' => array_keys($googleCalendars),
            'merged' => $mergedCalendars,
            'start' => $start,
            'end' => $end,
        ]));

        // Try cache first (configurable TTL via services.calendar.cache_ttl)
        if (!$debug) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $optParams = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c', strtotime($start)),
            'timeMax' => date('c', strtotime($end)),
        ];

        $all_events = [];
        $clients = [];
        $authFailures = [];
        $fetchFailures = [];

        foreach ($googleCalendars as $cal_id => $calendar) {
            $account = $calendar['account'] ?? config('services.google.default_account', 'default');

            // Get or create calendar service for this account
            if (! isset($clients[$account])) {
                try {
                    $clients[$account] = $this->googleAuth->getCalendarService($account);
                } catch (\Exception $e) {
                    $authFailures[$account] = $e->getMessage();
                    if ($debug) {
                        error_log("Failed to get calendar service for account {$account}: " . $e->getMessage());
                    }

                    continue;
                }
            }

            $service = $clients[$account];

            try {
                $this->mergeCalendar($service, $optParams, $cal_id, $calendar, $all_events, $debug);
            } catch (\Exception $e) {
                $fetchFailures[$cal_id] = $e->getMessage();
                if ($debug) {
                    error_log("Failed to fetch calendar {$cal_id}: " . $e->getMessage());
                }

                continue;
            }
        }

        // If we have authentication failures and no events, throw an error
        if (empty($all_events) && ! empty($authFailures)) {
            $accounts = implode(', ', array_keys($authFailures));
            throw new \Exception("Authentication failed for Google accounts: {$accounts}. Please re-authenticate.");
        }

        // If we have fetch failures but some events, log warnings
        if (! empty($fetchFailures)) {
            foreach ($fetchFailures as $calId => $error) {
                error_log("Calendar fetch warning for {$calId}: {$error}");
            }
        }

        // Process merged calendars
        $events_out = [];
        foreach ($all_events as $id => $event) {
            if (count($event['calendars']) > 1) {
                sort($event['calendars']);
                $event['calendars'] = array_unique($event['calendars']);

                $merged = implode('-', $event['calendars']);

                if (isset($mergedCalendars[$merged])) {
                    $event['backgroundColor'] = $mergedCalendars[$merged]['color'];
                } else {
                    $event['backgroundColor'] = '#AAA';
                }

                $event['borderColor'] = ColorHelper::adjustBrightness($event['backgroundColor'], -25);

                $bullets = '';
                foreach ($event['calendars'] as $cal_id) {
                    if (isset($googleCalendars[$cal_id]['emoji'])) {
                        $bullets .= $googleCalendars[$cal_id]['emoji'];
                    }
                }
            }

            $events_out[] = $event;
        }

        // Cache the results (configurable TTL, skip if debug mode)
        if (!$debug) {
            $cacheResult = Cache::put($cacheKey, $events_out, config('services.calendar.cache_ttl', 15 * 60));
            if (!$cacheResult) {
                Log::warning('Failed to cache Google Calendar events');
            }
        }

        return $events_out;
    }

    /**
     * Fetch events from a single calendar
     */
    public function fetchCalendarEvents(string $accountId, string $calendarId, ?string $start = null, ?string $end = null): array
    {
        $start = $start ?? date('Y-m-01');
        $end = $end ?? date('Y-m-30');

        $optParams = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c', strtotime($start)),
            'timeMax' => date('c', strtotime($end)),
        ];

        $service = $this->googleAuth->getCalendarService($accountId);
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();

        $events_out = [];
        foreach ($events as $event) {
            $start = $event->start->dateTime ?? $event->start->date;
            $end = $event->end->dateTime ?? $event->end->date;

            $events_out[] = [
                'title' => $event->getSummary(),
                'allDay' => $event->start->date ? true : false,
                'id' => $event->getId(),
                'start' => $start,
                'end' => $end,
            ];
        }

        return $events_out;
    }

    /**
     * Merge calendar events into the all_events array
     */
    private function mergeCalendar(Google_Service_Calendar $service, array $optParams, string $cal_id, array $calendar, array &$all_events, bool $debug = false): void
    {
        $results = $service->events->listEvents($calendar['src'], $optParams);
        $events = $results->getItems();

        foreach ($events as $event) {
            if ($event->eventType == 'workingLocation') {
                continue;
            }

            $start = $event->start->dateTime ?? $event->start->date;
            $end = $event->end->dateTime ?? $event->end->date;

            $start_obj = new DateTimeImmutable($start);
            $start_obj = $start_obj->setTimezone(new DateTimeZone('UTC'));
            $end_obj = new DateTimeImmutable($end);
            $end_obj = $end_obj->setTimezone(new DateTimeZone('UTC'));

            $declined = false;
            if ($event->attendees) {
                foreach ($event->attendees as $attendee) {
                    if ($attendee->email == $calendar['src'] && $attendee->responseStatus == 'declined') {
                        $declined = true;
                        break;
                    }
                }
            }

            $summary = $event->summary ?? '';
            if ($declined) {
                $summary = '<strike>' . $summary . '</strike>';
            }

            $clean_summary = StringHelper::removeEmoji($summary);
            $clean_summary = trim($clean_summary);

            $event_id = sha1($start_obj->format('c') . $end_obj->format('c') . $clean_summary);

            if (isset($all_events[$event_id])) {
                $all_events[$event_id]['calendars'][] = $cal_id;
            } else {
                $margin = $background = $calendar['color'];

                if (! $clean_summary) {
                    $colour = $this->themeService->getTheme() == 'nighttime' ? '#000' : '#FFF';
                    $margin = $background = $colour;
                }

                $all_events[$event_id] = [
                    'allDay' => $event->start->date ? true : false,
                    'title' => $summary,
                    'first' => $calendar['src'],
                    'clean' => $clean_summary,
                    'cleancount' => bin2hex($clean_summary),
                    'id' => $event->id,
                    'end' => $end,
                    'start' => $start,
                    'calendars' => [$cal_id],
                    'backgroundColor' => $margin,
                    'borderColor' => $background,
                ];
            }
        }
    }

    /**
     * Check authentication status for all configured accounts
     */
    public function checkAuthenticationStatus(array $googleCalendars): array
    {
        $status = [];
        $checkedAccounts = [];

        foreach ($googleCalendars as $cal_id => $calendar) {
            $account = $calendar['account'] ?? config('services.google.default_account', 'default');

            // Don't check the same account multiple times
            if (isset($checkedAccounts[$account])) {
                $status[$cal_id] = $checkedAccounts[$account];

                continue;
            }

            try {
                $service = $this->googleAuth->getCalendarService($account);
                // Test actual API access by making a lightweight call
                $service->calendarList->listCalendarList(['maxResults' => 1]);
                $authStatus = ['authenticated' => true, 'error' => null];
            } catch (\Exception $e) {
                $authStatus = ['authenticated' => false, 'error' => $e->getMessage()];
            }

            $checkedAccounts[$account] = $authStatus;
            $status[$cal_id] = $authStatus;
        }

        return $status;
    }
}
