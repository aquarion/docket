<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com https://dailyphoto.aquarionics.com https://live.dailyphoto.aquarionics.com; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com ;">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <script src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>
  <script src="https://unpkg.com/ical.js@1.5.0/build/ical.min.js" crossorigin="anonymous"></script>

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

  <link rel="stylesheet" href="{{ route('calendars.css') }}">
  @if($festival == 'christmas')
  <link rel="stylesheet" href="/static/css/christmas.css">
  @endif

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

<body class="theme-{{ $theme }} @if($festival) festival-{{ $festival }} @endif">
  <div id="app">
    <div id="calendars">
      @foreach($ical_calendars as $calendar)
      <div class="calendar" data-calendar="{{ $calendar['id'] }}">
        <!-- Calendar content will be loaded via JavaScript -->
      </div>
      @endforeach

      @foreach($google_calendars as $calendar)
      <div class="calendar" data-calendar="{{ $calendar['id'] }}">
        <!-- Calendar content will be loaded via JavaScript -->
      </div>
      @endforeach
    </div>
  </div>

  <script src="{{ route('docket.js') }}"></script>
  @if(config('app.debug') && isset($git_branch))
  <div class="debug-info">Branch: {{ $git_branch }}</div>
  @endif
</body>

</html>