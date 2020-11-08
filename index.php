<?PHP

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/radiator.lib.php';


?><html>
<head>
<meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.mapbox.com; font-src 'self' https://fonts.gstatic.com https://fonts.gstatic.com ;">

<script src='node_modules/ical.js/build/ical.js'></script>

<script src="https://twemoji.maxcdn.com/v/latest/twemoji.min.js" crossorigin="anonymous"></script>


<script src='https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.css' rel='stylesheet' />

<link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Wire+One&display=swap" rel="stylesheet">

<meta name="viewport" content = "width = device-width, initial-scale = 1.0, minimum-scale = 1, maximum-scale = 1, user-scalable = no" />
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="white" />
<link rel="apple-touch-startup-image" href="radiator.png">
<meta name="mobile-web-app-capable" content="yes">


<link rel="apple-touch-icon" sizes="180x180" href="icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="icons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">
<link rel="manifest" href="icons/site.webmanifest">
<link rel="mask-icon" href="icons/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="icons/favicon.ico">
<meta name="msapplication-TileColor" content="#ffc40d">
<meta name="msapplication-config" content="icons/browserconfig.xml">
<meta name="theme-color" content="#ffffff">


<title>Information Radiator</title>

<link href="static/style.css?v=<?PHP echo md5(file_get_contents('static/style.css')); ?>" rel="stylesheet">
<style type="text/css">

body {
	margin: 0;
}

div {

}

.marker {
  background-image: url('mapbox-icon.png');
  background-size: cover;
  width: 27px;
  height: 25px;
  border-radius: 50%;
  border: 1px solid black;
  cursor: pointer;
}

img.emoji {
   height: 1em;
   width: 1em;
   margin: 0 .05em 0 .1em;
   vertical-align: -0.1em;
}

.fyr_marker {
  background-image: url('https://art.istic.net/iconography/maps/fyr_icon.png');
}

.aq_marker {
  background-image: url('https://art.istic.net/iconography/maps/aq_icon.png');
}
<?PHP
	$template = 'a.cal-%2$s { background-color: %1$s; }'."\n";
	foreach($google_calendars as $name => $data){
		printf($template, $data['color'], $name);
	}
?>
</style>



<link rel='stylesheet' href='node_modules/fullcalendar/dist/fullcalendar.css' />
<script src='node_modules/jquery/dist/jquery.min.js'></script>
<script src='node_modules/moment/min/moment.min.js'></script>
<script src='node_modules/fullcalendar/dist/fullcalendar.js'></script>

</head>

<body id="<?PHP print(THEME) ?>">

<div id='calendar'></div>

<div id="datetime"> </div>

<a class="weatherwidget-io" href="https://forecast7.com/en/51d75n1d21/ox3-7nh/" data-label_1="Hubris" data-label_2="WEATHER" data-font="Open Sans Condensed" data-icons="Climacons Animated" data-days="5" data-theme="weather_one" >Hubris WEATHER</a>
<script>
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src='https://weatherwidget.io/js/widget.min.js';fjs.parentNode.insertBefore(js,fjs);}}(document,'script','weatherwidget-io-js');
</script>


<div id='map' style='width: 100%; height: 275px; display: inline-block'></div>


<canvas id="countdown" width="50" height="50"></canvas>

<script type="text/javascript">

function dateOrdinal(d) {
    return (31==d||21==d||1==d?"st":22==d||2==d?"nd":23==d||3==d?"rd":"th")
};

var currentMarkers=[];

var handleError = function(error){
	console.log('--- Error Follows:')
	console.log(error)
}

map = null;

updateMap = function(data, textresult, jsXDR){
	//console.log(data)
	//console.log(textresult)
	//console.log(map);

	if (currentMarkers!==null) {
	    for (var i = currentMarkers.length - 1; i >= 0; i--) {
	      currentMarkers[i].remove();
	    }
	}
	var fyr_marker = document.createElement('div');
	fyr_marker.className = 'marker fyr_marker'

	var aq_marker  = document.createElement('div');
	aq_marker.className = 'marker aq_marker'

	var llb = new mapboxgl.LngLatBounds();

	var i = 0;
	var len = data.length;
	for (; i < len; i++) {
	  item = data[i]


	  if(!item.location){
	  	console.log("Skipped "+item.id);
	  	console.log(item);
	  	continue;
	  }


	  if (item.id == "MTE5MDgzMzc~"){


	  	var aq = new mapboxgl.LngLat(item.location.longitude, item.location.latitude);
	  	console.log('Found Aq at ',item.location.longitude, item.location.ltaitude)
	    llb.extend(aq)

	    // // create a HTML element for each feature
	    // var aqmark = document.createElement('div');
	    // aqmark.className = 'marker';
	    // // make a marker for each feature and add to the map
	    oneMarker = new mapboxgl.Marker(aq_marker)
	      .setLngLat(aq)
	      .addTo(map);
	    currentMarkers.push(oneMarker);
	  }

	  if (item.id == "MTAxNzI2NDM5Mg~~"){
	  	var fyr = new mapboxgl.LngLat(item.location.longitude, item.location.latitude);
	  	console.log('Found Fyr at ',item.location.longitude, item.location.latitude)
	    llb.extend(fyr)

	    // // create a HTML element for each feature
	    // var fyrmark = document.createElement('div');
	    // fyrmark.className = 'marker';
	    // // make a marker for each feature and add to the map
	    oneMarker = new mapboxgl.Marker(fyr_marker)
	      .setLngLat(fyr)
	      .addTo(map);
	    currentMarkers.push(oneMarker);
	  }
	}

	//console.log(llb);

	map.llb = llb;

	map.fitBounds(llb, { padding: 60, maxZoom: 16  } )

}

function update_ical(calendarUrl, start, end, timezone, callback) {
	var callback = callback;
	$.get(calendarUrl).then(function (data) {
		// parse the ics data
		var jcalData = ICAL.parse(data.trim());
		var comp = new ICAL.Component(jcalData);
		var eventComps = comp.getAllSubcomponents("vevent");
		// console.log(JSON.stringify(eventComps));
		// map them to FullCalendar events

		var rangeStart = ICAL.Time.fromJSDate(start.toDate());
		var rangeEnd   = ICAL.Time.fromJSDate(end.toDate());

		var events = []
		$(eventComps).each(function (index, item) {
			var event = new ICAL.Event(item);
			if (item.getFirstPropertyValue("class") == "PRIVATE") {
				return;
			}
			if (event.isRecurrenceException() ) {
				return;
			}

			duration = event.duration

			if ( event.isRecurring() ) {
				// if Recurring
				var recur = item.getFirstPropertyValue('rrule');
				var dtstart = item.getFirstPropertyValue('dtstart');
				var iter = recur.iterator(dtstart);
				for (var next = iter.next(); next; next = iter.next()) {
					if (next.compare(rangeStart) < 0) {
						continue;
					}
					if (next.compare(rangeEnd) > 0) {
						continue;
					}
					var end = item.getFirstPropertyValue("dtend");
					end.addDuration(duration)
					events.push( {
						"title":    item.getFirstPropertyValue("summary"),
						"start":    next.toJSDate(),
						"end":      end.toJSDate(),
						"location": item.getFirstPropertyValue("location")
					} );
				}

			} else {
			// end if recurring
				events.push( {
					"title": item.getFirstPropertyValue("summary") + ";",
					"start": item.getFirstPropertyValue("dtstart").toJSDate(),
					"end": item.getFirstPropertyValue("dtend").toJSDate(),
					"location": item.getFirstPropertyValue("location")
				} );
			}
		});
		callback(events);
	});
}

calendars = [
	{ "url" : "https://altru.istic.net/radiator/all-calendars.php" },
	// { events : pr_cal, color: "#a24db8", textColor: 'white' }
];

radFirstDay = 1; // Set first day to Monday

// Smol version
if (window.innerHeight < 950 ){
	var radDefaultView = 'basicWeek';
	var radCalHeight = 400;
	d = new Date();
	dow = d.getDay();
	if(dow == 0){
		radFirstDay = 0;
	}
} else {
	var radDefaultView = 'month';
	var radCalHeight = 600;
}


<?PHP
	$template = <<<EOF
	calendars.push(
		{
			events : function (start, end, timezone, callback){
					events = update_ical("%s", start, end, timezone, callback)
				},
			color: "%s",
			textColor: '%s'
		}
	)
EOF;
foreach($ical_calendars as $index => $cal){
	// printf($template, $cal['src'], $cal['color'], '#FFFFFF');
	printf($template, $cal['src'], $cal['color'], '#FFFFFF');
}


if(THEME == "nighttime"){
	$map_url = 'mapbox://styles/aquarion/cj656i7c261pn2rolp2i4ptsh';
} else {
	$map_url = 'mapbox://styles/mapbox/streets-v11';
}

?>

$(function() {

  // page is now ready, initialize the calendar...

	$('#calendar').fullCalendar({
		height: radCalHeight,
		eventSources: calendars,
		error: handleError,
		defaultView: radDefaultView,
		firstDay: radFirstDay,
		fixedWeekCount: false,

		eventAfterAllRender: function() {
				twemoji.parse(document.body);
			}
		}
	)

		mapboxgl.accessToken = 'pk.eyJ1IjoiYXF1YXJpb24iLCJhIjoiQzRoeUpwZyJ9.gIhABGtR7UMR-LZUJGRW0A';
	map = new mapboxgl.Map({
	container: 'map',
	center: [-1.2, 51.75], // starting position [lng, lat]
	zoom: 10, // starting zoom
	style: '<?PHP print($map_url); ?>', // Basic
	// style: '', // Terminal
	// style: 'mapbox://styles/aquarion/ck6qknw4x4yoy1ipfvkhyuqko',
	});


  	$.get('data/fof.json',updateMap);

  	window.setTimeout(function(){
		map.fitBounds(map.llb, { padding: 60, maxZoom: 16, duration: 5000  } )
	}, 1000 * 5);
});

window.setInterval( function(){
	document.location.reload(true);
} , 1000 * 60 * 60);

window.setInterval( function(){
	$.get('data/fof.json',updateMap);
} , 1000 * 300 );

window.setInterval( function(){
	now = new Date();
	mins = now.getMinutes();
	hours = now.getHours();
	if (mins < 10){
		mins = "0" + mins;
	}
	if (hours < 10){
		hours = "0" + hours;
	}
	time = hours + ":" + mins;

	var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
	const o_date = new Intl.DateTimeFormat('en', options);
	const f_date = (m_ca, m_it) => Object({...m_ca, [m_it.type]: m_it.value});
	const m_date = o_date.formatToParts().reduce(f_date, {});

	const today = m_date;

	strToday = today.weekday + " " + today.month + " " + today.day + "<sup>"+dateOrdinal(today.day)+"</sup>";

	$('#date').html(strToday);
	$('#time').html(time);
	$('#datetime').html(time + " &ndash; " + strToday);


} , 1000);


circle = {
	x : 0,
	y : 0,
	radius : false,
	curPerc : 0,
	counterClockwise : false,
	circ : Math.PI * 2,
	quart : Math.PI / 2,

	drawCircle : function(id){
		canvas = document.getElementById(id);
		context = canvas.getContext('2d');
		circle.x = canvas.width / 2;
		circle.y = canvas.height / 2;
		circle.radius = 10;
		 context.lineWidth = 3;
		circle.endPercent = 85;
		circle.curPerc = 0;

		 context.strokeStyle = '#ad2323';
		 context.shadowOffsetX = 0;
		 context.shadowOffsetY = 0;
		 context.shadowBlur = 4;
		 context.shadowColor = '#656565';

		 circle.animate(0, id)
	},


	animate : function(current, id) {
		canvas = document.getElementById(id);
		context = canvas.getContext('2d');


	     context.clearRect(0, 0, canvas.width, canvas.height);
	     context.beginPath();
	     context.arc(circle.x, circle.y, circle.radius, -(circle.quart), ((circle.circ) * current) - circle.quart, false);
	     context.stroke();

	 }

}

circle.drawCircle('countdown');

var percent = 0;

seconds = 900 * 1000

window.setInterval( function(){
	//.fullCalendar( ‘refetchEvents’ )
	// $('#number').html(parseInt($('#number').html())+1);
	if(percent <= 1.02){
		circle.animate(percent, 'countdown');
		percent += .01;
		// console.log(percent);
	} else {
		console.log('Refreshing');
		$('#calendar').fullCalendar( 'refetchEvents' );
		percent = 0;
	}
} , 300 * 1000 ); // basically seconds, 1800 = 30 minutes

window.setInterval( function(){
	//console.log("Hi")
	map.fitBounds(map.llb, { padding: 60, maxZoom: 16  } )
} , 60* 1000 ); // basically seconds, 1800 = 30 minutes


</script>
</body>
</html>
