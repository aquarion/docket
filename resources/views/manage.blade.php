<!DOCTYPE html>
<html>

<head>
  @if (!config("app.debug"))
  <meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com https://dailyphoto.aquarionics.com https://live.dailyphoto.aquarionics.com {{ config('app.url') }}; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com; ">
  @endif
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link href="https://fonts.googleapis.com/css?family=Playfair Display" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sometype+Mono&amp;display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.gstatic.com">

  <meta name="viewport" content="width = device-width, initial-scale = 1.0, minimum-scale = 1, maximum-scale = 1, user-scalable = no" />
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <link rel="apple-touch-startup-image" href="/static/icons/calendar.png">
  <meta name="mobile-web-app-capable" content="yes">

  <!-- Favicons and Icons -->
  <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png">
  <link rel="shortcut icon" href="/static/icons/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png">
  <link rel="mask-icon" href="/static/icons/safari-pinned-tab.svg" color="#5bbad5">
  <link rel="manifest" href="/static/icons/site.webmanifest">
  <meta name="msapplication-config" content="/static/icons/browserconfig.xml">

  <title>Manage Calendars - Docket</title>

  @vite(['resources/css/manage.scss'])

  <!-- Base Calendar Styles -->
  <link rel="stylesheet" href="{{ route('calendars.css') }}">
  @if($festival == 'christmas')
  <link rel="stylesheet" href="/static/generated/christmas.css">
  @elseif($festival == 'easter')
  <link rel="stylesheet" href="/static/generated/easter.css">
  @endif

  <!-- Toastify CSS for notifications -->
  <link rel="stylesheet" href="/static/generated/toastify.css">

  <!-- Toastify JavaScript for notifications (must load before Vite bundle) -->
  <script type="text/javascript" src="/static/generated/toastify.js"></script>

  <script type="text/javascript">
    // Initialize DocketConfig stub with festival value - must be before Vite bundle loads
    if (typeof DocketConfig === 'undefined') {
      window.DocketConfig = {
        constants: {
          FESTIVAL: "{{ $festival ?? '' }}",
        },
      };
    }
  </script>

  @vite('resources/js/app.js')
</head>

<body class="{{ $theme }}">
  <div class="management-container">
    <div class="header-section">
      <h1>📅 Calendar Management</h1>
      <div class="nav-links">
        <a href="{{ route('home') }}">← Back to Calendar</a>
        @if(Auth::check())
        <span>{{ Auth::user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
          @csrf
          <button type="submit" style="background: none; border: none; color: inherit; text-decoration: underline; cursor: pointer;">Logout</button>
        </form>
        @endif
      </div>
    </div>

    <!-- Calendar Sets Section -->
    <div class="section">
      <h2>📋 Calendar Sets</h2>
      <p>Calendar sets group related calendars together. Each set can contain multiple calendar sources.</p>
      <button class="add-button" onclick="CalendarManager.showAddSetModal()">+ Add Calendar Set</button>

      <div id="calendar-sets-loading" class="loading">
        Loading calendar sets...
      </div>
      <div id="calendar-sets-list" class="list-container" style="display: none;">
        <!-- Calendar sets will be loaded here -->
      </div>
      <div id="calendar-sets-empty" class="empty-state" style="display: none;">
        No calendar sets found. Create your first set to get started!
      </div>
    </div>

    <!-- Calendar Sources Section -->
    <div class="section">
      <h2>🔗 Calendar Sources</h2>
      <p>Calendar sources are individual calendars from Google Calendar or iCal URLs.</p>
      <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <button class="add-button" onclick="CalendarManager.showGoogleCalendarModal()">+ Add Google Calendars</button>
        <button class="add-button" onclick="CalendarManager.showAddSourceModal('ical')">+ Add iCal URL</button>
      </div>

      <div id="calendar-sources-loading" class="loading">
        Loading calendar sources...
      </div>
      <div id="calendar-sources-list" class="list-container" style="display: none;">
        <!-- Calendar sources will be loaded here -->
      </div>
      <div id="calendar-sources-empty" class="empty-state" style="display: none;">
        No calendar sources found. Add your first source to get started!
      </div>
    </div>

    @if (config('app.debug') && isset($git_branch))
    <div class="section">
      <h2>🔧 Debug Info</h2>
      <p><strong>Git Branch:</strong> {{ $git_branch }}</p>
    </div>
    @endif
  </div>

  <!-- Google Calendar Selection Modal -->
  <div id="google-calendar-selection-modal" class="modal">
    <div class="modal-content">
      <h3>Select Google Calendars</h3>
      <p>Choose which Google calendars you want to add as calendar sources:</p>

      <div id="google-calendars-loading" class="loading">
        Loading your Google calendars...
      </div>

      <div id="google-calendars-list" style="display: none;">
        <!-- Google calendars will be loaded here -->
      </div>

      <div id="google-calendars-error" class="empty-state" style="display: none; color: #dc3545;">
        Failed to load Google calendars. Please make sure you're authenticated with Google.
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="CalendarManager.hideGoogleCalendarModal()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="CalendarManager.addSelectedGoogleCalendars()" disabled id="add-google-calendars-btn">Add Selected</button>
      </div>
    </div>
  </div>

  <!-- Calendar Set Modal -->
  <div id="calendar-set-modal" class="modal">
    <div class="modal-content">
      <h3 id="calendar-set-modal-title">Add Calendar Set</h3>
      <form id="calendar-set-form">
        <div class="form-group">
          <label for="set-key">Key (unique identifier):</label>
          <input type="text" id="set-key" name="key" required>
        </div>
        <div class="form-group">
          <label for="set-name">Display Name:</label>
          <input type="text" id="set-name" name="name" required>
        </div>
        <div class="form-group">
          <label for="set-emoji">Emoji (optional):</label>
          <div class="emoji-input-container">
            <input type="text" id="set-emoji" name="emoji" maxlength="2" class="emoji-display" readonly>
            <button type="button" class="emoji-picker-btn" data-target="set-emoji">📂</button>
          </div>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" id="set-default" name="is_default"> Set as default
          </label>
        </div>
        <div class="form-group">
          <label>Calendar Sources:</label>
          <div class="checkbox-grid" id="calendar-sources-selection">
            <div class="loading-text">Loading available calendar sources...</div>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="CalendarManager.hideSetModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Calendar Source Modal -->
  <div id="calendar-source-modal" class="modal">
    <div class="modal-content">
      <h3 id="calendar-source-modal-title">Add Calendar Source</h3>
      <form id="calendar-source-form">
        <div class="form-group">
          <label for="source-key">Key (unique identifier):</label>
          <input type="text" id="source-key" name="key" required>
        </div>
        <div class="form-group">
          <label for="source-name">Display Name:</label>
          <input type="text" id="source-name" name="name" required>
        </div>
        <div class="form-group">
          <label for="source-type">Type:</label>
          <select id="source-type" name="type" required>
            <option value="ical">iCal URL</option>
            <option value="google">Google Calendar</option>
          </select>
        </div>
        <div class="form-group">
          <label for="source-src">Source URL/ID:</label>
          <input type="text" id="source-src" name="src" required>
        </div>
        <div class="form-group">
          <label for="source-color">Color:</label>
          <input type="color" id="source-color" name="color" value="#3788d8">
        </div>
        <div class="form-group">
          <label for="source-emoji">Emoji (optional):</label>
          <div class="emoji-input-container">
            <input type="text" id="source-emoji" name="emoji" maxlength="2" class="emoji-display" readonly>
            <button type="button" class="emoji-picker-btn" data-target="source-emoji">🔗</button>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="CalendarManager.hideSourceModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Initialize calendar management when page loads
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof CalendarManager !== 'undefined') {
        CalendarManager.init();
      } else {
        console.error('CalendarManager not loaded');
      }
    });
  </script>
</body>

</html>