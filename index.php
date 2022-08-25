<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/radiator.lib.php';


?><html>
<head>
<meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com https://dailyphoto.aquarionics.com https://live.dailyphoto.aquarionics.com; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com ;">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<script src='node_modules/ical.js/build/ical.js'></script>

<script src="https://twemoji.maxcdn.com/v/latest/twemoji.min.js" crossorigin="anonymous"></script>


<script src='https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.css' rel='stylesheet' />

<link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Wire+One&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Oxygen+Mono&display=swap" rel="stylesheet">

<meta name="viewport" content = "width = device-width, initial-scale = 1.0, minimum-scale = 1, maximum-scale = 1, user-scalable = no" />
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

<link href="static/style.css?v=<?php echo md5(file_get_contents('static/style.css')); ?>" rel="stylesheet">
<link href="https://dailyphoto.aquarionics.com/element.css.php?element=%23photo" rel="stylesheet">

<style type="text/css">

<?php
    $a_template = 'a.cal-%2$s { background-color: %1$s; }'."\n";
$b_template = '.txtcal-%2$s { color: %1$s; }'."\n";
foreach ($google_calendars as $name => $data) {
    printf($a_template, $data['color'], $name);
    printf($b_template, $data['color'], $name);
}

foreach ($ical_calendars as $name => $data) {
    printf($a_template, $data['color'], $name);
    printf($b_template, $data['color'], $name);
}

foreach ($merged_calendars as $name => $color) {
    printf($a_template, $color, $name);
    printf($b_template, $color, $name);
}

?>
</style>



<link rel='stylesheet' href='node_modules/fullcalendar/dist/fullcalendar.css' />
<script src='node_modules/jquery/dist/jquery.min.js'></script>
<script src='node_modules/moment/min/moment.min.js'></script>
<script src='node_modules/fullcalendar/dist/fullcalendar.js'></script>

</head>

<body id="<?php print(THEME) ?>">
<div id='calendar'></div>

<div id="weather">
<a class="weatherwidget-io" href="https://forecast7.com/en/51d75n1d21/ox3-7nh/" data-label_1="Hubris" data-label_2="WEATHER" data-font="Open Sans Condensed" data-icons="Climacons Animated" data-days="5" data-theme="weather_one" >Hubris WEATHER</a>
<script>
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src='https://weatherwidget.io/js/widget.min.js';fjs.parentNode.insertBefore(js,fjs);}}(document,'script','weatherwidget-io-js');
</script>
</div>
<div id="datetime"> </div>

<div id='map'   style='width: 100%; height: 275px; display: inline-block'></div>

<div id='nextUp'>
</div>


<div id='switch'>
<?php
if (isset($_GET['version']) && $_GET['version'] == "work") {
    echo '<a href="/?version=home">🏠</a>';
} else {
    echo '<a href="/?version=work">🏢</a>';
}
?>
</div>


<canvas id="countdown" width="50" height="50"></canvas>

<script type="text/javascript" src="./radiator.js.php">


</script>
</body>
</html>
