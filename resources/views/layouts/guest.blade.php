<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>@yield('title', 'Sign in') · ICT PMS — Jimma University</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
  body.guest-body {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
      radial-gradient(circle at 15% 10%, rgba(201,134,44,.10), transparent 45%),
      radial-gradient(circle at 85% 90%, rgba(31,75,75,.14), transparent 45%),
      var(--bg);
  }
  .guest-card { width: 380px; padding: 34px 32px; }
  .guest-brand { display:flex; align-items:center; gap:10px; margin-bottom:26px; }
  .guest-brand .brand-mark { width:36px; height:36px; border-radius:9px; background:linear-gradient(155deg,var(--accent),var(--accent-dark)); display:flex; align-items:center; justify-content:center; font-family:'Space Grotesk'; font-weight:700; color:#1B1200; }
  .guest-brand .t1 { font-family:'Space Grotesk'; font-weight:600; font-size:16px; }
  .guest-brand .t2 { font-size:12px; color:var(--ink-soft); }
  .field { margin-bottom:16px; }
  .field label { display:block; font-size:12.5px; font-weight:600; color:var(--ink-soft); margin-bottom:6px; }
  .field input, .field select {
    width:100%; border:1px solid var(--line); border-radius:8px; padding:10px 12px;
    font-size:13.5px; font-family:inherit; background:var(--surface); color:var(--ink); box-sizing:border-box;
  }
  .field input:focus, .field select:focus { outline:none; border-color:var(--primary); }
  .field-hint { font-size:11.5px; color:var(--ink-faint); margin-top:5px; line-height:1.5; }
  .field-error { color:var(--danger); font-size:12px; margin-top:6px; }
  .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .guest-hint {
    margin-top:22px; padding:13px 14px; background:var(--surface-alt); border-radius:9px;
    font-size:11.8px; color:var(--ink-soft); line-height:1.65;
  }
  .guest-hint b { color:var(--ink); font-family:'IBM Plex Mono'; font-weight:600; }
</style>
</head>
<body class="guest-body">
  <div class="card guest-card">
    <div class="guest-brand">
      <div class="brand-mark">JU</div>
      <div>
        <div class="t1">ICT PMS</div>
        <div class="t2">Jimma University</div>
      </div>
    </div>
    @yield('content')
  </div>
</body>
</html>
