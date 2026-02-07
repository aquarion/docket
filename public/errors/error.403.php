<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden</title>
  <meta http-equiv="refresh" content="30"> <!-- Auto refresh every 30 seconds -->
  <link rel="stylesheet" href="/build/assets/app-BwhyZnaQ.css">
  <style>
    body {
      font-family: 'Playfair Display', sans-serif;
      margin: 2rem;
    }
  </style>
</head>

<body>
  <h1>Forbidden</h1>
  <p>Docket is IP blocked from your current location (<?php echo $_SERVER['HTTP_X_FORWARDED_FOR']; ?>). This page will refresh in <span id="countdown">30</span> seconds</p>
  <script>
    var countdownElement = document.getElementById("countdown");
    var countdown = 30;
    var countdownInterval = setInterval(function() {
      countdown--;
      countdownElement.textContent = countdown;
      if (countdown <= 0) {
        clearInterval(countdownInterval);
        location.reload();
      }
    }, 1000);
  </script>
  <p><a href="/">Return to Home</a></p>
</body>

</html>