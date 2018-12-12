<?PHP 

// $hour = date('H');
// $day = date('D');
// if ($hour < 19 || $hour > 21){
	header('location: index.php');
// 	die();	
// }

function random_pic($dir = 'uploads')
{
    $files = glob($dir . '/*.*');
    $file = array_rand($files);
    return $files[$file];
}

?>
<!DOCTYPE html>
<html lang="">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Title Page</title>
		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		<link rel="stylesheet" href="/background.css.php" crossorigin="anonymous">
		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
<link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
<style type="text/css">
body {
	font-family: 'Oxygen', sans-serif;

    background-image: url("<?PHP print random_pic('wallpaper'); ?>");
    background-repeat: no-repeat; 
    background-size: cover;
	background-attachment: fixed; 
	background-position:  center;
}


#datetime {
	display: inline-block; 
	vertical-align: top;
	width: 380px;
	position: absolute;
	bottom: 10px;
	left: 10px;
	color: rgba(255,255,255,.8);
}

#time {
	font-size: 100pt;
	text-align: center;
}

#date {
	font-size: 28pt;
	text-align: center;
}


		</style>
	</head>
	<body>
		<h1 class="text-center"> </h1>
		<!-- jQuery -->
		<script src="//code.jquery.com/jquery.js"></script>
		<!-- Bootstrap JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
		<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->


	<div id="datetime">
			<div id="time">88:88</div>
			<div id="date">DDD MMM dd YYYY</div>

	</div>


<script type="text/javascript">


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
} , 1000 * 60 * 10);


</script>
	</body>
</html>