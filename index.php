<?PHP 

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/radiator.lib.php';

// header('location: wallpaper.php');
// die();

include("calendars.inc.php");

?><html>
<head>


<link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
<link href="static/style.css" rel="stylesheet">
<style type="text/css">
<?PHP
	$template = 'a.cal-%2$s { background-color: %1$s; }'."\n";
	foreach($calendars as $name => $data){
		printf($template, $data['color'], $name);
	}
?>
</style>



<link rel='stylesheet' href='node_modules/fullcalendar/dist/fullcalendar.css' />
<script src='node_modules/jquery/dist/jquery.min.js'></script>
<script src='node_modules/moment/min/moment.min.js'></script>
<script src='node_modules/fullcalendar/dist/fullcalendar.js'></script>

</head>

<body>

<div id='calendar'></div>
<!-- 
<div id="rightbar">
	<h2>Network in</h2>
	<img src="https://treacle.mine.nu/bandwidthd/Total-1-R.png">
	<h2>Network out</h2>
	<img src="https://treacle.mine.nu/bandwidthd/Total-1-S.png">
</div>
 -->
<div id="bottom">
	<iframe style="display: inline-block; float: left;" id="forecast_embed" type="text/html" frameborder="0" height="245px" width="650px" 
	src="//forecast.io/embed/#lat=51.768&lon=-1.2&name=Hope:&units=si&font-face-name=Oxygen&font-face-url=<?PHP echo urlencode("https://fonts.gstatic.com/s/oxygen/v5/78wGxsHfFBzG7bRkpfRnCQ.woff2"); ?>"
	> </iframe>


	<div id="datetime">
			<div id="time">88:88</div>
			<div id="date">DDD MMM dd YYYY</div>

	</div>

	<div style="display: inline-block;" id="calendarkey">
		<ul>
<?PHP
$template = '			<li style="color: %s">%s &ndash; &#x2588;</li>';
foreach($calendars as $name => $data){
	printf($template, $data['color'], $data['name']);
}
?>
		</ul>
	</div>
</div>
<canvas id="countdown" width="50" height="50"></canvas>

<script type="text/javascript">


var handleError = function(error){
	console.log('---')
	console.log(error)
}


calendars = <?PHP echo fullcal_json($calendars) ?>


$(function() {

  // page is now ready, initialize the calendar...

  $('#calendar').fullCalendar({
  	height: 600,
  	width: 1400,
    eventSources: calendars,
    error: handleError
  }
  )

});

window.setInterval( function(){
	document.location.reload(true);
} , 1000 * 60 * 60);

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
	$('#time').html(time);
	$('#date').html(now.toDateString());
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

seconds = 900

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
} , 9000); // basically seconds, 1800 = 30 minutes



</script>
</body>
</html>
