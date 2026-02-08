<?php

namespace App\Http\Controllers;

use App\Models\CalendarSet;
use App\Services\CalendarService;
use App\Services\GoogleCalendarService;
use App\Services\ICalProxyService;
use App\Services\ThemeService;
use App\Support\Git;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private CalendarService $calendarService,
        private ThemeService $themeService,
        private ICalProxyService $icalProxyService,
        private GoogleCalendarService $googleCalendarService
    ) {}

    /**
     * Display the main calendar page
     */
    public function index(Request $request)
    {
        $filteredConfig = $this->getFilteredCalendars($request);

        // Get calendar sets from database (for authenticated users) or fallback to config
        $calendarSetsData = [];
        if (auth()->check()) {
            $calendarSets = CalendarSet::where('user_id', auth()->id())
                ->where('is_active', true)
                ->get();

            foreach ($calendarSets as $set) {
                $calendarSetsData[$set->key] = [
                    'name' => $set->name,
                    'emoji' => $set->emoji,
                ];
            }
        }

        // Fallback to static config if no database sets or user not authenticated
        if (empty($calendarSetsData)) {
            $calendarSetsData = config('calendars.calendar_sets', []);
        }

        $view = [
            'ical_calendars' => $filteredConfig['ical_calendars'],
            'google_calendars' => $filteredConfig['google_calendars'],
            'merged_calendars' => $filteredConfig['merged_calendars'],
            'theme' => $this->themeService->getTheme(),
            'festival' => $this->themeService->getFestival(),
            'calendar_set' => $filteredConfig['calendar_set_id'],
            'calendar_sets' => $calendarSetsData,
        ];

        if (config('app.debug')) {
            $view['git_branch'] = Git::currentBranch();
        }

        return view('index', $view);
    }

    /**
     * Display a specific calendar
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'cal' => 'required|string',
            'account' => 'required|string',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date',
        ]);

        try {
            $events = $this->googleCalendarService->fetchCalendarEvents(
                $validated['account'],
                $validated['cal'],
                $validated['start'] ?? null,
                $validated['end'] ?? null
            );

            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display all calendars
     */
    public function all(Request $request)
    {
        $validated = $request->validate([
            'start' => 'sometimes|date',
            'end' => 'sometimes|date',
            'debug' => 'sometimes|boolean',
            'calendar_set' => 'sometimes|string',
        ]);

        try {
            $filteredConfig = $this->getFilteredCalendars($request);

            $events = $this->googleCalendarService->fetchAllCalendarEvents(
                $filteredConfig['google_calendars'],
                $filteredConfig['merged_calendars'],
                $validated['start'] ?? null,
                $validated['end'] ?? null,
                $validated['debug'] ?? false
            );

            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve calendar CSS
     */
    public function css(Request $request)
    {
        $filteredConfig = $this->getFilteredCalendars($request);

        return response()
            ->view('calendars-css', ['calendars' => $filteredConfig])
            ->header('Content-Type', 'text/css')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Serve calendar JavaScript
     */
    public function js(Request $request)
    {
        $filteredConfig = $this->getFilteredCalendars($request);

        return response()
            ->view('docket-js', [
                'ical_calendars' => $filteredConfig['ical_calendars'],
                'google_calendars' => $filteredConfig['google_calendars'],
                'merged_calendars' => $filteredConfig['merged_calendars'],
                'calendar_set' => $filteredConfig['calendar_set_id'],
                'festival' => $this->themeService->getFestival(),
            ])
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * iCal proxy endpoint
     */
    public function icalProxy(Request $request)
    {
        $validated = $request->validate([
            'cal' => 'required|string',
            'raw' => 'sometimes|boolean',
            'nocache' => 'sometimes|boolean',
        ]);

        $calendarId = $validated['cal'];
        $raw = $validated['raw'] ?? false;
        $useCache = ! ($validated['nocache'] ?? false);

        try {
            $result = $this->icalProxyService->fetchCalendar($calendarId, $raw, $useCache);

            return response($result['content'])
                ->header('Content-Type', $result['content_type'])
                ->header('X-Cached', $result['cached'] ? 'Yes' : 'No');
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Display the calendar management page
     */
    public function manage(Request $request)
    {
        $view = [
            'theme' => $this->themeService->getTheme(),
            'festival' => $this->themeService->getFestival(),
        ];

        if (config('app.debug')) {
            $view['git_branch'] = Git::currentBranch();
        }

        return view('manage', $view);
    }

    /**
     * Get user's Google calendars for selection
     */
    public function getGoogleCalendars(Request $request)
    {
        $calendars = $this->googleCalendarService->getUserCalendars();

        return response()->json([
            'data' => $calendars,
        ]);
    }

    /**
     * API endpoint for calendar data
     */
    public function apiIndex(Request $request)
    {
        $filteredConfig = $this->getFilteredCalendars($request);

        return response()->json([
            'data' => [
                'ical_calendars' => $filteredConfig['ical_calendars'],
                'google_calendars' => $filteredConfig['google_calendars'],
                'merged_calendars' => $filteredConfig['merged_calendars'],
                'calendar_set' => $filteredConfig['calendar_set_id'],
            ],
        ]);
    }

    /**
     * Get filtered calendars based on request calendar_set parameter
     */
    private function getFilteredCalendars(Request $request): array
    {
        $availableSetIds = $this->calendarService->getAvailableSetIds();

        $validated = $request->validate([
            'calendar_set' => 'sometimes|in:'.implode(',', $availableSetIds),
        ]);

        $calendarSetId = $validated['calendar_set'] ?? $this->calendarService->getDefaultSetId();
        $calendarConfig = $this->calendarService->loadCalendarConfig();
        $filteredConfig = $this->calendarService->filterBySet($calendarConfig, $calendarSetId);

        // Add calendar_set_id for easy access
        $filteredConfig['calendar_set_id'] = $calendarSetId;

        return $filteredConfig;
    }
}
