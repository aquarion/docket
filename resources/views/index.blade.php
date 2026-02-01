<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com https://dailyphoto.aquarionics.com https://live.dailyphoto.aquarionics.com; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com ;">
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
  @endif

  <!-- Vite Assets -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "Docket",
      "description": "Personal Calendar Dashboard",
      "applicationCategory": "ProductivityApplication",
      "operatingSystem": "Any"
    }
  </script>
</head>

<body class="{{ $theme }}" role="application" aria-label="Calendar Dashboard">
  <a href="#main-content" class="skip-link">Skip to main content</a>

  @if($festival == 'christmas')
  @include('christmas')
  @endif

  @if(config('app.debug') && isset($git_branch))
  <div class="devmode-ribbon" role="banner" aria-label="Development mode indicator">
    <a target="_blank" href="https://github.com/aquarion/docket" rel="noopener noreferrer">
      Branch {{ $git_branch }}
    </a>
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
      </div>
    </div>
  </div>

  <canvas id="countdown" width="50" height="50" aria-label="Refresh countdown indicator"></canvas>

  <script type="text/javascript" src="{{ route('docket.js') }}?calendar_set={{ $calendar_set }}"></script>
</body>

</html>