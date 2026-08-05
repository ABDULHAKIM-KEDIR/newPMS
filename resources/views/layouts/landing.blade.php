<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>@yield('title', 'ICT PMS — Jimma University')</title>
<meta name="description" content="The project management system for Jimma University's ICT Directorate — projects, tasks, budgets, and teams in one place.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'])
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>[x-cloak] { display: none !important; } html { scroll-behavior: smooth; }</style>
</head>
<body class="lp-body">
  @yield('content')
</body>
</html>
