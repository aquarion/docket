<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com https://dailyphoto.aquarionics.com https://live.dailyphoto.aquarionics.com; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com ;">
  <meta
    name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <!-- script src='node_modules/ical.js/build/ical.min.js'></script -->

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


  <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v=zXvYGBOMEg">
  <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v=zXvYGBOMEg">
  <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v=zXvYGBOMEg">
  <link rel="manifest" href="/static/icons/site.webmanifest?v=zXvYGBOMEg">
  <link rel="mask-icon" href="/static/icons/safari-pinned-tab.svg?v=zXvYGBOMEg" color="#5bbad5">
  <link rel="shortcut icon" href="/static/icons/favicon.ico?v=zXvYGBOMEg">
  <meta name="apple-mobile-web-app-title" content="Docket">
  <meta name="application-name" content="Docket">
  <meta name="msapplication-TileColor" content="#da532c">
  <meta name="msapplication-config" content="/static/icons/browserconfig.xml?v=zXvYGBOMEg">
  <meta name="theme-color" content="#ffffff">


  <title>Docket</title>

  <link
    href="static/css/style.css" rel="stylesheet">


  <link rel='stylesheet' href='./calendars.css.php?version={{ calendar_set }}' />
  <script src='static/js/date-utils.js'></script>
  <script src='node_modules/suncalc/suncalc.js'></script>

  <script src="node_modules/toastr/build/toastr.min.js"></script>
  <link rel="stylesheet" href="node_modules/toastr/build/toastr.min.css">

</head>

<body class="token">

  <h1>Hey Look, An Auth Token!</h1>

  <pre><a href="#" id="copyCode" title="Copy auth code to clipboard" ><img src="/static/icons/copy.svg"></a><code id="code"><?PHP echo $_GET['code'] ?></code></pre>
  <p>Enter that into the terminal that's waiting for it</p>

  <script>
    document.querySelector('#copyCode').addEventListener('click', function(event) {
      event.preventDefault();
      var code = document.getElementById('code').textContent;
      navigator.clipboard.writeText(code).then(function() {
        console.log('Code copied to clipboard!');
      }, function(err) {
        alert('Failed to copy code: ', err);
      });
    });
  </script>


</body>

</html>