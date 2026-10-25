<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Log in | Grill Fisher &amp; BBQ</title>
  <meta name="theme-color" content="#0b0b0c">
  <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
  <link rel="apple-touch-icon" href="{{ asset('img/icon-180.png') }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Grill Fisher">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
  <div class="login-page">
    <aside class="login-art">
      <div class="logo">
        <img src="{{ asset('img/logo.webp') }}" alt="" width="46" height="46">
        <div><b>Grill Fisher &amp; BBQ</b><small>Business dashboard</small></div>
      </div>
      <div style="position:relative;z-index:1">
        <h2>Run your restaurant with <span>clear numbers</span>, every day.</h2>
        <p>Sales, expenses and profit for Grill Fisher &amp; BBQ, all in one place.</p>
        <div class="login-feats">
          <div><span class="kpi-ic gold"><x-icon name="receipt" size="16" /></span><span><b>Sales</b> recorded in a few taps from your menu</span></div>
          <div><span class="kpi-ic gold"><x-icon name="wallet" size="16" /></span><span><b>Expenses</b> tracked by category and supplier</span></div>
          <div><span class="kpi-ic gold"><x-icon name="chart" size="16" /></span><span><b>Reports</b> with daily profit and Excel export</span></div>
        </div>
      </div>
      <small>Ameer Road, Bilal Gunj, Lahore</small>
    </aside>

    <div class="login-side">
      <form method="POST" action="{{ url('/login') }}" class="login-card">
        @csrf
        <div class="logo login-logo">
          <img src="{{ asset('img/logo.webp') }}" alt="" width="88" height="88">
          <div><b>Grill Fisher &amp; BBQ</b><small>Business dashboard</small></div>
        </div>
        <h1>Welcome back</h1>
        <p class="sub">Log in to your dashboard.</p>
        @if ($errors->any())
          <div class="errors" role="alert"><x-icon name="alert" />{{ $errors->first() }}</div>
        @endif
        <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@grillfisher.com" required autofocus autocomplete="username"></div>
        <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
        <label class="check"><input type="checkbox" name="remember" value="1"> Keep me logged in</label>
        <button class="btn" type="submit">Log in</button>
      </form>
    </div>
  </div>
  <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
