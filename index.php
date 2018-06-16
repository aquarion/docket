<html>
<head>
		<link rel="stylesheet" href="static/flipclock/flipclock.css">

<link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
<style type="text/css">
body {
	font-family: 'Oxygen', sans-serif;
}

#calendarkey {

	position: absolute;
	top: 0;
	right: 0;
}

#calendarkey ul {
	list-style: none;
	padding-left: 0;
}

#calendarkey ul li{
	height: 1.5em;
	text-align: right;
}

#time {
	font-size: 100pt;
	text-align: center;
}

#date {
	font-size: 28pt;
	text-align: center;
}

#datetime {
	height:100px;
	display: inline-block; 
	vertical-align: top;
	width: 380px;
}

#bottom {
	width: 1200px;
	height: 245px;
	position: relative;
}

#rightbar {
	float: right; width: 300px;
	text-align: right;
	position: relative;
	overflow: hidden;
}

#rightbar img {
	/*max-width: 100%;*/
	float: right;
}
#rightbar h2 {
	 width: 300px;
	 text-align: left;
	 font-size: 10pt;
}

</style>

<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script>

</script>
</head>

<body onLoad="setFocus()">
<br>

<?php 

include("calendars.inc.php");

$options = array(
	'showTitle=0',
	'showNav=0',
	'showPrint=0',
	'showTabs=0',
	'showCalendars=0',
	'showTz=0',
	'height=600',
	'wkst=2',
	'bgcolor=%23ffffff',
	'ctz=Europe%2FLondon',
	
	);


foreach($calendars as $calendar){
	$options[] = 'src='.urlencode($calendar['src']);
	$options[] = 'color='.urlencode($calendar['color']);
}

?>

<div id="rightbar">
	<h2>Network in</h2>
	<img src="https://treacle.mine.nu/bandwidthd/Total-1-R.png">
	<h2>Network out</h2>
	<img src="https://treacle.mine.nu/bandwidthd/Total-1-S.png">
</div>
<iframe src="https://calendar.google.com/calendar/embed?<?PHP echo implode("&amp;", $options); ?>" style="border-width:0" width="1200" height="600" frameborder="0" scrolling="no"></iframe>

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
	printf($template, $data['color'], $name);
}
?>
		</ul>
	</div>
</div>

<script type="text/javascript">


window.setInterval( function(){
	document.getElementById('forecast_embed').contentWindow.location.reload(true);
} , 1000 * 60 * 15);

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


window.setInterval( function(){
	window.location.reload(true);
} , 1000 * 60 * 120);



</script>
</body>
</html>
