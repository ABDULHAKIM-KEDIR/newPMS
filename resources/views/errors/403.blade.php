<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Access denied · ICT PMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css'])
</head>
<body style="height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg);">
  <div class="card card-pad" style="max-width:420px; text-align:center; padding:40px 36px;">
    <div style="width:52px; height:52px; border-radius:14px; background:var(--danger-soft); color:var(--danger); display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
    </div>
    <h1 style="font-family:'Space Grotesk'; font-size:19px; margin-bottom:8px;">You don't have access to this</h1>
    <p style="font-size:13.5px; color:var(--ink-soft); line-height:1.6; margin-bottom:24px;">
      {{ $exception->getMessage() ?: "Your role doesn't include the permission this page or action needs. If you think that's wrong, ask an ICT Director or System Administrator to check your role under Roles & Access." }}
    </p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="justify-content:center;">Back to dashboard</a>
  </div>
</body>
</html>
