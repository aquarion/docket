<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">

  <title>{{ config('app.name') }} - Auth Token</title>

  <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v=zXvYGBOMEg">
  <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v=zXvYGBOMEg">
  <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v=zXvYGBOMEg">
  <link rel="shortcut icon" href="/static/icons/favicon.ico?v=zXvYGBOMEg">

  <link href="/static/css/style.css" rel="stylesheet">

  <style>
    body.token {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 2rem;
      text-align: center;
    }

    body.token h1 {
      margin-bottom: 2rem;
      font-size: 2rem;
    }

    body.token pre {
      background: #f5f5f5;
      padding: 1.5rem;
      border-radius: 8px;
      position: relative;
      margin: 1rem 0;
      max-width: 100%;
      overflow-x: auto;
    }

    body.token code {
      font-family: 'Courier New', monospace;
      font-size: 1.2rem;
      word-break: break-all;
    }

    body.token #copyCode {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      cursor: pointer;
      opacity: 0.6;
      transition: opacity 0.2s;
    }

    body.token #copyCode:hover {
      opacity: 1;
    }

    body.token #copyCode img {
      width: 24px;
      height: 24px;
    }

    body.token p {
      margin-top: 1rem;
      font-size: 1.1rem;
    }

    .success-message {
      color: #28a745;
      margin-top: 0.5rem;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .success-message.show {
      opacity: 1;
    }
  </style>
</head>

<body class="token">
  <h1>Hey Look, An Auth Token!</h1>

  @if($message)
  <div style="background: {{ str_contains($message, 'Error') ? '#f8d7da' : '#d4edda' }}; 
                    color: {{ str_contains($message, 'Error') ? '#721c24' : '#155724' }}; 
                    padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    {{ $message }}
  </div>
  @endif

  @if($account)
  <p style="margin-bottom: 1rem;">Account: <strong>{{ $account }}</strong></p>
  @endif

  <pre><code id="code">{{ $code }}</code></pre>

  <a href="#" id="copyCode" role="button" aria-label="Copy auth token to clipboard">
    📋 Copy code
  </a>

  <p>Enter that into the terminal that's waiting for it</p>
  <p class="success-message" id="successMessage">✓ Code copied to clipboard!</p>

  <script>
    document.querySelector('#copyCode').addEventListener('click', function(event) {
      event.preventDefault();
      var code = document.getElementById('code').textContent.trim();
      navigator.clipboard.writeText(code).then(function() {
        console.log('Code copied to clipboard!');
        var message = document.getElementById('successMessage');
        message.classList.add('show');
        setTimeout(function() {
          message.classList.remove('show');
        }, 3000);
      }, function(err) {
        console.error('Failed to copy code: ', err);
        alert('Failed to copy code to clipboard');
      });
    });
  </script>
</body>

</html>