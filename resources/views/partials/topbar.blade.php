<div class="topbar">
  <div class="crumb">@yield('crumb', '<b>Dashboard</b>')</div>
  <form class="search" method="GET" action="{{ route('search') }}" style="cursor:text;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects, tasks, people…" style="border:none; background:none; outline:none; font:inherit; color:inherit; width:100%;">
  </form>
  <a href="{{ route('notifications.index') }}" class="icon-btn" title="Notifications">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
    @if(($unreadCount ?? \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count()) > 0)<span class="dot"></span>@endif
  </a>
</div>
