<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>@yield('title', 'ICT PMS — Jimma University')</title>
<meta name="description" content="The project management system for Jimma University's ICT Directorate — projects, tasks, budgets, and teams in one place.">
<script>
  // Applied before first paint to avoid a flash of the wrong theme.
  try {
    var t = localStorage.getItem('lp-theme');
    if (t) document.documentElement.setAttribute('data-lp-theme', t);
  } catch (e) {}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js', 'resources/js/landing.js'])
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>[x-cloak] { display: none !important; } html { scroll-behavior: smooth; }</style>
</head>
<body class="lp-body">
  <div class="lp-progress" id="lpProgress"></div>
  @yield('content')
  <button class="lp-to-top" id="lpToTop" aria-label="Scroll to top">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
  </button>
</body>
</html>
