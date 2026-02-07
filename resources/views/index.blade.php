<!DOCTYPE html>
<html>

<head>
  @if (!config("app.debug"))
  <meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com https://dailyphoto.aquarionics.com https://live.dailyphoto.aquarionics.com {{ config('app.url') }}; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com; ">
  @endif
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

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

  <title>Docket - Personal Calendar</title>

  <!-- Dynamic Calendar Styles -->
  <link rel="stylesheet" href="{{ route('calendars.css') }}?calendar_set={{ $calendar_set }}">
  @if($festival == 'christmas')
  <link rel="stylesheet" href="/static/generated/christmas.css">
  @elseif($festival == 'easter')
  <link rel="stylesheet" href="/static/generated/easter.css">
  @endif

  <!-- Toastify CSS for notifications -->
  <link rel="stylesheet" href="/static/generated/toastify.css">

  <script type="text/javascript">
    // Initialize DocketConfig stub with festival value - must be before Vite bundle loads
    if (typeof DocketConfig === 'undefined') {
      window.DocketConfig = {
        constants: {
          FESTIVAL: "{{ $festival ?? '' }}",
        },
      };
    }

    // Initialize FestivalUtils stub - must be before Vite bundle loads
    if (typeof FestivalUtils === 'undefined') {
      window.FestivalUtils = {
        getCallback: () => null,
      };
    }
  </script>

  <!-- Vite Assets -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Toastify JavaScript for notifications -->
  <script type="text/javascript" src="/static/generated/toastify.js"></script>

  <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebApplication",
      "name": "Docket",
      "description": "Personal Calendar Dashboard",
      "applicationCategory": "ProductivityApplication",
      "operatingSystem": "Any"
    }
  </script>
</head>

<body class="{{ $theme }}" role="application" aria-label="Calendar Dashboard">
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- User Menu -->
  @auth
  <div class="user-menu" role="toolbar" aria-label="User menu">
    <div class="user-info">
      @if(auth()->user()->avatar)
      <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="user-avatar">
      @endif
      <span class="user-name">{{ auth()->user()->name }}</span>
      <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="logout-btn" aria-label="Sign out">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 012 2v2h-2V4H5v16h9v-2h2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h9z"/>
          </svg>
        </button>
      </form>
    </div>
  </div>
  @else
  <div class="auth-menu" role="toolbar" aria-label="Authentication menu">
    <a href="{{ route('auth.google') }}" class="login-btn" aria-label="Sign in with Google">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
      </svg>
      Sign In
    </a>
  </div>
  @endauth

  @if($festival == 'christmas')
  @include('christmas')
  @elseif($festival == 'easter')
  @include('easter')
  @endif

  @if(config('app.debug') && isset($git_branch))
  <div class="devmode-ribbon" role="banner" aria-label="Development mode indicator">
    <a target="_blank" href="https://github.com/aquarion/docket" rel="noopener noreferrer">
      Branch {{ $git_branch }}
    </a>
  </div>

  <!-- Festival Selector (Debug Mode) -->
  <div class="debug-festival-selector" role="toolbar" aria-label="Festival selector">
    <label for="festival-select">Festival:</label>
    <select id="festival-select" aria-label="Select festival for testing">
      <option value="none" @selected(!$festival || ($festival !=='christmas' && $festival !=='easter' ))>None</option>
      <option value="christmas" @selected($festival==='christmas' )>🎄 Christmas</option>
      <option value="easter" @selected($festival==='easter' )>🐰 Easter</option>
    </select>
  </div>
  @endif

  <main id="main-content">
    <div id='calendar' role="img" aria-label="Calendar visualization"></div>
    <div id="datetime" role="timer" aria-live="polite" aria-label="Current date and time"></div>
    <div id='nextUp' role="region" aria-label="Upcoming events" aria-live="polite"></div>
  </main>

  <nav id='switch' role="navigation" aria-label="View switcher">
    @php
    $available_sets = array_keys($calendar_sets);
    $current_index = array_search($calendar_set, $available_sets);
    $next_index = ($current_index + 1) % count($available_sets);
    $next_set = $available_sets[$next_index] ?? $available_sets[0];
    $next_emoji = $calendar_sets[$next_set]['emoji'] ?? '🔄';
    @endphp
    <button id="calendar-selector-btn" aria-label="Select calendar set" title="Select calendar set">📅</button>
  </nav>

  <!-- Calendar Selector Modal -->
  <div id="calendar-selector-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-title">Select Calendar Set</h2>
        <button class="modal-close" aria-label="Close modal">&times;</button>
      </div>
      <div class="modal-body">
        <ul class="calendar-set-list">
          @foreach($calendar_sets as $set_id => $set_config)
          <li class="calendar-set-item {{ $set_id === $calendar_set ? 'active' : '' }}" data-set-id="{{ $set_id }}">
            <a href="/?calendar_set={{ $set_id }}" class="calendar-set-link">
              @if(isset($set_config['emoji']))
              <span class="calendar-set-emoji">{{ $set_config['emoji'] }}</span>
              @endif
              <span class="calendar-set-name">{{ $set_config['name'] ?? ucfirst($set_id) }}</span>
              @if($set_id === $calendar_set)
              <span class="calendar-set-active-indicator">✓</span>
              @endif
            </a>
          </li>
          @endforeach
        </ul>
        <div class="modal-footer">
          <button id="check-auth-btn" type="button" class="auth-settings-btn">
            <span class="btn-icon">🔐</span>
            <span class="btn-text">Authentication Settings</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Authentication Settings Modal -->
  <div id="auth-settings-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="auth-modal-title">Google Calendar Authentication</h2>
        <button class="modal-close" aria-label="Close modal">&times;</button>
      </div>
      <div class="modal-body">
        <div id="auth-loading" class="auth-loading">
          <span class="loading-spinner">⏳</span>
          <span>Checking authentication status...</span>
        </div>
        <div id="auth-content" style="display: none;">
          <p class="auth-description">
            Manage Google Calendar authentication for your calendar accounts.
          </p>
          <div id="auth-accounts-list" class="auth-accounts-list">
            <!-- Account authentication status will be populated by JavaScript -->
          </div>
        </div>
        <div id="auth-error" class="auth-error" style="display: none;">
          <span class="error-icon">❌</span>
          <span id="auth-error-message">Failed to check authentication status</span>
        </div>
      </div>
    </div>
  </div>

  <canvas id="countdown" width="50" height="50" aria-label="Refresh countdown indicator"></canvas>

  <script type="text/javascript" src="{{ route('docket.js') }}?calendar_set={{ $calendar_set }}&festival={{ $festival ?? '' }}"></script>
</body>

</html>