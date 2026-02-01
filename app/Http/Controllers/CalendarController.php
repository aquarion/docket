<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CalendarController extends Controller
{
  /**
   * Display the main calendar page
   */
  public function index(Request $request)
  {
    // Calendar version logic
    $calendarSet = $request->get('version') === 'work' ? 'work' : 'home';

    // Load calendar configuration
    $calendarConfig = $this->loadCalendarConfig();

    // Get sun info for theme
    $theme = $this->getTheme();

    // Festival detection
    $festival = date('m') == 12 ? 'christmas' : false;

    $view = [
      'ical_calendars'   => $calendarConfig['ical_calendars'] ?? [],
      'google_calendars' => $calendarConfig['google_calendars'] ?? [],
      'merged_calendars' => $calendarConfig['merged_calendars'] ?? [],
      'theme' => $theme,
      'festival' => $festival,
      'calendar_set' => $calendarSet
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
    $calendarConfig = $this->loadCalendarConfig();

    return response()
      ->view('calendars.css', ['calendars' => $calendarConfig])
      ->header('Content-Type', 'text/css');
  }

  /**
   * Serve calendar JavaScript
   */
  public function js(Request $request)
  {
    return response()
      ->view('docket.js')
      ->header('Content-Type', 'application/javascript');
  }

  /**
   * iCal proxy endpoint
   */
  public function icalProxy(Request $request)
  {
    // Implementation for icalproxy.php functionality
    return response('', 200);
  }

  /**
   * Token endpoint
   */
  public function token(Request $request)
  {
    // Implementation for token.php functionality
    return response('', 200);
  }

  /**
   * Load calendar configuration
   */
  private function loadCalendarConfig()
  {
    $configFile = base_path('calendars.inc.php');

    if (file_exists($configFile)) {
      include $configFile;
      return [
        'ical_calendars' => $ical_calendars ?? [],
        'google_calendars' => $google_calendars ?? [],
        'merged_calendars' => $merged_calendars ?? []
      ];
    }

    return [
      'ical_calendars' => [],
      'google_calendars' => [],
      'merged_calendars' => []
    ];
  }

  /**
   * Get current theme based on time of day
   */
  private function getTheme()
  {
    $lat = config('services.location.latitude', env('MY_LAT', 51.5074));
    $lon = config('services.location.longitude', env('MY_LON', -0.1278));

    $sunInfo = date_sun_info(time(), $lat, $lon);
    $sunset = $sunInfo['sunset'];
    $sunrise = $sunInfo['sunrise'];

    return (time() > $sunset || time() < $sunrise) ? 'nighttime' : 'daytime';
  }

  /**
   * Get current git branch
   */
  private function getGitBranch()
  {
    $gitHead = base_path('.git/HEAD');
    if (file_exists($gitHead)) {
      $head = file_get_contents($gitHead);
      return trim(str_replace('ref: refs/heads/', '', $head));
    }
    return 'unknown';
  }
}
