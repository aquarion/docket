<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">

  <title>{{ config('app.name') }} - Login</title>

  <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v=zXvYGBOMEg">
  <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v=zXvYGBOMEg">
  <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v=zXvYGBOMEg">
  <link rel="manifest" href="/static/icons/site.webmanifest?v=zXvYGBOMEg">
  <link rel="mask-icon" href="/static/icons/safari-pinned-tab.svg?v=zXvYGBOMEg" color="#000000">
  <link rel="shortcut icon" href="/static/icons/favicon.ico?v=zXvYGBOMEg">
  <meta name="msapplication-TileColor" content="#000000">
  <meta name="msapplication-config" content="/static/icons/browserconfig.xml?v=zXvYGBOMEg">
  <meta name="theme-color" content="#000000">

  <link rel="stylesheet" href="/static/css/style.css">

  <style>
    .auth-container {
      max-width: 400px;
      margin: 4rem auto;
      padding: 3rem;
      border: 1px solid #ddd;
      border-radius: 12px;
      background: #fff;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .auth-title {
      margin-bottom: 2rem;
      font-size: 2rem;
      color: #333;
      font-weight: 300;
    }
    
    .auth-description {
      margin-bottom: 2.5rem;
      color: #666;
      line-height: 1.5;
    }
    
    .google-login-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 2rem;
      background: #4285f4;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 500;
      text-decoration: none;
      transition: background-color 0.2s;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .google-login-btn:hover {
      background: #3367d6;
      color: white;
    }
    
    .google-icon {
      width: 20px;
      height: 20px;
      background: white;
      border-radius: 2px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #4285f4;
    }
    
    .auth-links {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid #eee;
    }
    
    .auth-links a {
      color: #666;
      text-decoration: none;
      font-size: 0.9rem;
    }
    
    .auth-links a:hover {
      text-decoration: underline;
    }
    
    .error-message {
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 6px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      color: #dc2626;
      font-size: 0.9rem;
    }
  </style>
</head>

<body>
  <div class="auth-container">
    <h1 class="auth-title">Welcome to {{ config('app.name') }}</h1>
    
    <p class="auth-description">
      Sign in with your Google account to access your personal calendar dashboard.
    </p>

    @if (session('error'))
      <div class="error-message">
        {{ session('error') }}
      </div>
    @endif

    <a href="{{ route('auth.google') }}" class="google-login-btn">
      <span class="google-icon">G</span>
      Continue with Google
    </a>

    <div class="auth-links">
      <p><a href="{{ route('home') }}">← Back to Calendar</a></p>
    </div>
  </div>
</body>

</html>