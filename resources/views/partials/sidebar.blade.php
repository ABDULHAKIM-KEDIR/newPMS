<div class="sidebar">
  <div class="brand">
    <div class="brand-mark">JU</div>
    <div class="brand-text">
      <div class="t1">ICT PMS</div>
      <div class="t2">Jimma University</div>
    </div>
  </div>
  <nav>
    <div class="nav-section">Overview</div>
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      <span>Dashboard</span>
    </a>

    <div class="nav-section">Work</div>
    <a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>
      <span>Projects</span><span class="nav-badge">{{ \App\Models\Project::count() }}</span>
    </a>
    <a href="{{ route('tasks.index') }}" class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      <span>My Tasks</span>
    </a>
    <a href="{{ route('teams.index') }}" class="nav-item {{ request()->routeIs('teams.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.7-3.5 3.3-5.5 6.5-5.5s5.8 2 6.5 5.5"/><circle cx="18" cy="8" r="2.6"/><path d="M15.8 14.7c2.4.4 4.1 2.1 4.7 5.3"/></svg>
      <span>Teams</span>
    </a>
    <a href="{{ route('budgets.index') }}" class="nav-item {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.2c0-1.2 1.1-2 2.5-2s2.5.9 2.5 2c0 3-5 1.6-5 4.6 0 1.2 1.1 2 2.5 2s2.5-.8 2.5-2"/></svg>
      <span>Budgets</span>
    </a>

    <div class="nav-section">System</div>
    <a href="{{ route('admin.roles') }}" class="nav-item {{ request()->routeIs('admin.roles') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.6 3.2 3.5.4-2.6 2.5.7 3.5L12 10.9 8.8 12.6l.7-3.5-2.6-2.5 3.5-.4L12 3Z"/><circle cx="12" cy="17" r="4"/></svg>
      <span>Roles &amp; Access</span>
    </a>
    <a href="{{ route('admin.audit') }}" class="nav-item {{ request()->routeIs('admin.audit') ? 'active' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h4"/></svg>
      <span>Audit Log</span>
    </a>
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
