<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
  /**
   * Create a new controller instance
   */
  public function __construct(private CalendarService $calendarService) {}

  /**
   * Display the main calendar page
   */
  public function index(Request $request)
  {
    $availableSetIds = $this->calendarService->getAvailableSetIds();

    $validated = $request->validate([
      'version' => 'sometimes|in:' . implode(',', $availableSetIds),
    ]);

    $calendarSetId = $validated['version'] ?? $this->calendarService->getDefaultSetId();
    $calendarConfig = $this->calendarService->loadCalendarConfig();
    $filteredConfig = $this->calendarService->filterBySet($calendarConfig, $calendarSetId);
    $theme = $this->getTheme();
    $festival = $this->getFestival();

    $view = [
      'ical_calendars' => $filteredConfig['ical_calendars'],
      'google_calendars' => $filteredConfig['google_calendars'],
      'merged_calendars' => $filteredConfig['merged_calendars'],
      'theme' => $theme,
      'festival' => $festival,
      'calendar_set' => $calendarSetId,
      'calendar_sets' => config('calendars.calendar_sets', []),
    ];

    if (config('app.debug')) {
      $view['git_branch'] = $this->getGitBranch();
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
    $availableSetIds = $this->calendarService->getAvailableSetIds();

    $validated = $request->validate([
      'version' => 'sometimes|in:' . implode(',', $availableSetIds),
    ]);

    $calendarSetId = $validated['version'] ?? $this->calendarService->getDefaultSetId();
    $calendarConfig = $this->calendarService->loadCalendarConfig();
    $filteredConfig = $this->calendarService->filterBySet($calendarConfig, $calendarSetId);

    return response()
      ->view('calendars.css', ['calendars' => $filteredConfig])
      ->header('Content-Type', 'text/css');
  }

  /**
   * Serve calendar JavaScript
   */
  public function js(Request $request)
  {
    $availableSetIds = $this->calendarService->getAvailableSetIds();

    $validated = $request->validate([
      'version' => 'sometimes|in:' . implode(',', $availableSetIds),
    ]);

    $calendarSetId = $validated['version'] ?? $this->calendarService->getDefaultSetId();
    $calendarConfig = $this->calendarService->loadCalendarConfig();
    $filteredConfig = $this->calendarService->filterBySet($calendarConfig, $calendarSetId);

    return response()
      ->view('docket.js', [
        'ical_calendars' => $filteredConfig['ical_calendars'],
        'google_calendars' => $filteredConfig['google_calendars'],
        'merged_calendars' => $filteredConfig['merged_calendars'],
        'calendar_set' => $calendarSetId,
      ])
      ->header('Content-Type', 'application/javascript');
  }

  /**
   * iCal proxy endpoint
   */
  public function icalProxy(Request $request)
  {
    // TODO: Implement iCal proxy functionality
    return response('', 501); // Not Implemented
  }

  /**
   * Token endpoint
   */
  public function token(Request $request)
  {
    // TODO: Implement token functionality
    return response('', 501); // Not Implemented
  }

  /**
   * Get current theme based on time of day
   */
  private function getTheme(): string
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
  private function getFestival(): ?string
  {
    return ((int) date('m')) === 12 ? 'christmas' : null;
  }

  /**
   * Get current git branch
   */
  private function getGitBranch(): string
  {
    $gitHead = base_path('.git/HEAD');

    if (! file_exists($gitHead)) {
      return 'unknown';
    }

    $head = file_get_contents($gitHead);

    if ($head === false) {
      return 'unknown';
    }

    return trim(str_replace('ref: refs/heads/', '', $head));
  }
}
