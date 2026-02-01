<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
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
    private ICalProxyService $icalProxyService
  ) {}

  /**
   * Display the main calendar page
   */
  public function index(Request $request)
  {
    $filteredConfig = $this->getFilteredCalendars($request);

    $view = [
      'ical_calendars' => $filteredConfig['ical_calendars'],
      'google_calendars' => $filteredConfig['google_calendars'],
      'merged_calendars' => $filteredConfig['merged_calendars'],
      'theme' => $this->themeService->getTheme(),
      'festival' => $this->themeService->getFestival(),
      'calendar_set' => $filteredConfig['calendar_set_id'],
      'calendar_sets' => config('calendars.calendar_sets', []),
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
    // Implementation for calendar.php functionality
    return view('calendar');
  }

  /**
   * Display all calendars
   */
  public function all(Request $request)
  {
    // Implementation for all-calendars.php functionality
    return view('all-calendars');
  }

  /**
   * Serve calendar CSS
   */
  public function css(Request $request)
  {
    $filteredConfig = $this->getFilteredCalendars($request);

    return response()
      ->view('calendars.css', ['calendars' => $filteredConfig])
      ->header('Content-Type', 'text/css');
  }

  /**
   * Serve calendar JavaScript
   */
  public function js(Request $request)
  {
    $filteredConfig = $this->getFilteredCalendars($request);

    return response()
      ->view('docket.js', [
        'ical_calendars' => $filteredConfig['ical_calendars'],
        'google_calendars' => $filteredConfig['google_calendars'],
        'merged_calendars' => $filteredConfig['merged_calendars'],
        'calendar_set' => $filteredConfig['calendar_set_id'],
      ])
      ->header('Content-Type', 'application/javascript');
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
   * Get filtered calendars based on request version parameter
   */
  private function getFilteredCalendars(Request $request): array
  {
    $availableSetIds = $this->calendarService->getAvailableSetIds();

    $validated = $request->validate([
      'version' => 'sometimes|in:' . implode(',', $availableSetIds),
    ]);

    $calendarSetId = $validated['version'] ?? $this->calendarService->getDefaultSetId();
    $calendarConfig = $this->calendarService->loadCalendarConfig();
    $filteredConfig = $this->calendarService->filterBySet($calendarConfig, $calendarSetId);

    // Add calendar_set_id for easy access
    $filteredConfig['calendar_set_id'] = $calendarSetId;

    return $filteredConfig;
  }
}
