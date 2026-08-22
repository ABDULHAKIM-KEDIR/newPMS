<div class="sidebar">
  <div class="brand">
    <div class="brand-mark">JU</div>
    <div class="brand-text">
      <div class="t1">ICT PMS</div>
      <div class="t2">Jimma University</div>
    </div>
  </div>
  <nav>
    @php
      $currentUser = auth()->user();
      $projectsCount = \App\Models\Project::count();
      $myTasksCount = \App\Models\Task::where('assigned_to', $currentUser->user_id)->whereNotIn('status', ['Done', 'Completed'])->count();
      $teamsCount = \App\Models\Team::count();
      $unreadNotifsCount = \App\Models\Notification::where('user_id', $currentUser->user_id)->where('is_read', false)->count();
    @endphp

    <div class="nav-section">Main</div>
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      <span>Dashboard</span>
    </a>

    <div class="nav-section">Work</div>
    @can('view_projects')
      <a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>
        <span>Projects</span>
        <span class="nav-badge">{{ $projectsCount }}</span>
      </a>
    @endcan

    @can('view_projects')
      <a href="{{ route('teams.index') }}" class="nav-item {{ request()->routeIs('teams.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.7-3.5 3.3-5.5 6.5-5.5s5.8 2 6.5 5.5"/><circle cx="18" cy="8" r="2.6"/><path d="M15.8 14.7c2.4.4 4.1 2.1 4.7 5.3"/></svg>
        <span>Teams</span>
        <span class="nav-badge">{{ $teamsCount }}</span>
      </a>
    @endcan

    @can('view_tasks')
      <a href="{{ route('tasks.index') }}" class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span>Tasks</span>
        @if ($myTasksCount > 0)
          <span class="nav-badge" style="background:var(--accent-soft); color:var(--accent-dark);">{{ $myTasksCount }}</span>
        @endif
      </a>
    @endcan

    <a href="{{ route('calendar.index') }}" class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>Calendar</span>
    </a>

    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      <span>Reports</span>
    </a>

    <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
      <span>Notifications</span>
      @if ($unreadNotifsCount > 0)
        <span class="nav-badge" style="background:#fee2e2; color:#b91c1c;">{{ $unreadNotifsCount }}</span>
      @endif
    </a>

    @if ($currentUser->can('manage_budgets') || $currentUser->isDirectorOrAdmin())
      <a href="{{ route('budgets.index') }}" class="nav-item {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.2c0-1.2 1.1-2 2.5-2s2.5.9 2.5 2c0 3-5 1.6-5 4.6 0 1.2 1.1 2 2.5 2s2.5-.8 2.5-2"/></svg>
        <span>Budgets</span>
      </a>
    @endif

    @if ($currentUser->can('manage_roles') || $currentUser->can('manage_users') || $currentUser->can('view_audit_logs') || $currentUser->can('manage_system_settings'))
      <div class="nav-section">System</div>
      @if ($currentUser->can('manage_roles') || $currentUser->can('manage_users'))
        <a href="{{ route('admin.roles') }}" class="nav-item {{ request()->routeIs('admin.roles') ? 'active' : '' }}">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.6 3.2 3.5.4-2.6 2.5.7 3.5L12 10.9 8.8 12.6l.7-3.5-2.6-2.5 3.5-.4L12 3Z"/><circle cx="12" cy="17" r="4"/></svg>
          <span>Roles &amp; Access</span>
        </a>
      @endif
      @can('manage_users')
        <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Users</span>
          <span class="nav-badge">{{ \App\Models\User::count() }}</span>
        </a>
      @endcan
      @can('view_audit_logs')
        <a href="{{ route('admin.audit') }}" class="nav-item {{ request()->routeIs('admin.audit') ? 'active' : '' }}">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h4"/></svg>
          <span>Audit Log</span>
        </a>
      @endcan
      @can('manage_system_settings')
        <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
          <span>Settings</span>
        </a>
      @endcan
    @endif
  </nav>
  </nav>
  <div class="sidebar-foot">
    @php $currentUser = auth()->user(); @endphp
    <div class="user-chip">
      <div class="avatar">{{ $currentUser->initials() }}</div>
      <div class="user-meta">
        <div class="name">{{ $currentUser->full_name }}</div>
        <div class="role">{{ optional($currentUser->roles->first())->role_name ?? 'Member' }}</div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="nav-item" style="margin-top:2px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        <span>Log out</span>
      </button>
    </form>
  </div>
</div>
