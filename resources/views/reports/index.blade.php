@extends('layouts.app')
@section('title', 'Reports & Analytics')
@section('crumb', 'Reports & Analytics')

@section('content')
<div class="page-head">
  <div>
    <h1>Reports &amp; Analytics</h1>
    <div class="page-sub">Project delivery metrics, team performance, workload distribution, and completion trends</div>
  </div>

  <!-- Filter Bar -->
  <form method="GET" action="{{ route('reports.index') }}"
    style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <select name="project_id" onchange="this.form.submit()"
      style="border:1px solid var(--line); border-radius:6px; padding:6px 12px; font-size:12.5px; font-family:inherit; background:var(--surface);">
      <option value="">All Projects</option>
      @foreach ($projects as $p)
        <option value="{{ $p->project_id }}" {{ (string) $projectId === (string) $p->project_id ? 'selected' : '' }}>
          {{ $p->project_name }}</option>
      @endforeach
    </select>

    <select name="team_id" onchange="this.form.submit()"
      style="border:1px solid var(--line); border-radius:6px; padding:6px 12px; font-size:12.5px; font-family:inherit; background:var(--surface);">
      <option value="">All Teams</option>
      @foreach ($teams as $t)
        <option value="{{ $t->team_id }}" {{ (string) $teamId === (string) $t->team_id ? 'selected' : '' }}>
          {{ $t->team_name }}</option>
      @endforeach
    </select>
  </form>
</div>

<!-- Executive Summary Cards -->
<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="card stat-card">
    <div class="stat-label">Active Projects</div>
    <div class="stat-value" style="font-size:26px;">{{ $activeProjects }} / {{ $totalProjects }}</div>
    <div class="stat-delta">Total project portfolio</div>
  </div>

  <div class="card stat-card">
    <div class="stat-label">Overall Completion Rate</div>
    <div class="stat-value" style="font-size:26px; color:var(--active);">{{ $overallProgress }}%</div>
    <div class="progressbar" style="margin-top:6px;">
      <div style="width:{{ $overallProgress }}%"></div>
    </div>
  </div>

  <div class="card stat-card">
    <div class="stat-label">Tasks Completed</div>
    <div class="stat-value" style="font-size:26px; color:var(--success);">{{ $completedTasks }} / {{ $totalTasks }}
    </div>
    <div class="stat-delta">{{ $inProgressTasks }} currently in progress</div>
  </div>

  <div class="card stat-card">
    <div class="stat-label">Overdue Tasks</div>
    <div class="stat-value" style="font-size:26px; color:{{ $overdueTasks > 0 ? 'var(--danger)' : 'var(--success)' }};">
      {{ $overdueTasks }}
    </div>
    <div class="stat-delta">{{ $blockedTasks }} tasks currently blocked</div>
  </div>
</div>

<!-- Section 1: Project Progress & Tasks Breakdown Charts -->
<div class="two-col" style="margin-bottom:20px;">
  <!-- Project Progress Comparison Chart -->
  <div class="card card-pad">
    <div class="card-title-row" style="margin-bottom:14px;">
      <h3 style="margin:0;">Project Progress Overview</h3>
      <span class="badge b-active">{{ $projects->count() }} Projects</span>
    </div>

    <div style="display:flex; flex-direction:column; gap:14px;">
      @forelse ($projects as $prj)
        @php
          $pProg = $prj->progressPercentage();
          $pTasks = $prj->allTasks();
          $pDone = $pTasks->filter(fn($t) => in_array($t->status, ['Done', 'Completed']))->count();
          $pTotal = $pTasks->count();
        @endphp
        <div style="background:var(--bg-subtle); padding:12px 14px; border-radius:8px; border:1px solid var(--line);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div>
              <a href="{{ route('projects.show', $prj) }}" style="font-weight:700; font-size:14px; color:var(--ink); text-decoration:none;">
                {{ $prj->project_name }}
              </a>
              @if ($prj->client)
                <span style="font-size:11.5px; color:var(--ink-soft); margin-left:6px;">({{ $prj->client }})</span>
              @endif
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="priority p-{{ strtolower($prj->priority ?: 'medium') }}" style="font-size:10px;">{{ $prj->priority ?: 'Medium' }}</span>
              <strong style="font-size:13px; color:var(--ink);">{{ $pProg }}%</strong>
            </div>
          </div>
          <div class="progressbar" style="margin-bottom:6px;"><div style="width:{{ $pProg }}%"></div></div>
          <div style="display:flex; justify-content:space-between; font-size:11.5px; color:var(--ink-soft);">
            <span>PM: {{ optional($prj->projectManager)->full_name ?? 'Unassigned' }}</span>
            <span>{{ $pDone }} of {{ $pTotal }} tasks completed</span>
          </div>
        </div>
      @empty
        <div style="text-align:center; padding:24px; color:var(--ink-faint);">No projects found.</div>
      @endforelse
    </div>
  </div>

  <!-- Tasks by Status & Priority Breakdown Charts -->
  <div class="card card-pad">
    <div class="card-title-row" style="margin-bottom:14px;">
      <h3 style="margin:0;">Task Status &amp; Priority Distribution</h3>
      <span class="badge b-planning">{{ $totalTasks }} Total Tasks</span>
    </div>

    <!-- Status Breakdown Visual Bars -->
    <div style="margin-bottom:20px;">
      <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--ink-muted); margin-bottom:8px;">Tasks by Status</div>
      @foreach ($statusStats as $stName => $stCount)
        @php
          $stPct = $totalTasks > 0 ? round(($stCount / $totalTasks) * 100) : 0;
          $stColor = match($stName) {
            'Completed' => 'var(--success)',
            'In Progress' => 'var(--accent)',
            'In Review' => 'var(--review)',
            'Blocked' => 'var(--danger)',
            default => 'var(--ink-muted)'
          };
        @endphp
        <div style="margin-bottom:8px;">
          <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:3px;">
            <span style="font-weight:600; color:var(--ink);">{{ $stName }}</span>
            <span style="color:var(--ink-soft);"><strong>{{ $stCount }}</strong> ({{ $stPct }}%)</span>
          </div>
          <div style="width:100%; height:8px; background:var(--bg-subtle); border-radius:999px; overflow:hidden;">
            <div style="width:{{ $stPct }}%; height:100%; background:{{ $stColor }}; border-radius:999px;"></div>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Priority Breakdown Visual Bars -->
    <div style="border-top:1px solid var(--line); padding-top:14px;">
      <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--ink-muted); margin-bottom:8px;">Tasks by Priority</div>
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:8px;">
        @foreach ($priorityStats as $prName => $prCount)
          @php $prPct = $totalTasks > 0 ? round(($prCount / $totalTasks) * 100) : 0; @endphp
          <div style="background:var(--bg-subtle); padding:10px 8px; border-radius:6px; text-align:center; border:1px solid var(--line);">
            <div class="priority p-{{ strtolower($prName) }}" style="display:inline-block; font-size:10px; margin-bottom:4px;">{{ $prName }}</div>
            <div style="font-size:18px; font-weight:800; color:var(--ink);">{{ $prCount }}</div>
            <div style="font-size:11px; color:var(--ink-muted);">{{ $prPct }}%</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

<!-- Section 2: Team Performance & Individual Member Workload -->
<div class="two-col" style="margin-bottom:20px;">
  <!-- Team Performance Table -->
  <div class="card card-pad">
    <div class="card-title-row" style="margin-bottom:12px;">
      <h3 style="margin:0;">Team Performance &amp; Efficiency</h3>
    </div>

    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Team</th>
            <th>Lead</th>
            <th>Tasks</th>
            <th>Done</th>
            <th>Progress</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($teamWorkload as $tw)
            <tr>
              <td class="cell-primary">
                <a href="{{ route('teams.show', $tw['team_id']) }}" style="color:var(--ink); text-decoration:none; font-weight:700;">
                  {{ $tw['team_name'] }}
                </a>
              </td>
              <td><span style="font-size:12px; color:var(--ink-soft);">{{ $tw['leader_name'] }}</span></td>
              <td><span class="mono" style="font-weight:600;">{{ $tw['total_tasks'] }}</span></td>
              <td><span class="mono" style="font-weight:600; color:var(--success);">{{ $tw['completed_tasks'] }}</span></td>
              <td style="min-width:110px;">
                <div style="display:flex; align-items:center; gap:6px;">
                  <div class="progressbar" style="flex:1;"><div style="width:{{ $tw['progress'] }}%"></div></div>
                  <span class="mono" style="font-size:11px;">{{ $tw['progress'] }}%</span>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Individual Member Workload -->
  <div class="card card-pad">
    <div class="card-title-row" style="margin-bottom:12px;">
      <h3 style="margin:0;">Workload by Team Member</h3>
    </div>

    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Member</th>
            <th>Department</th>
            <th>Total</th>
            <th>Completed</th>
            <th>Pending</th>
            <th>Rate</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($memberWorkload->take(8) as $mw)
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <div class="avatar" style="width:22px; height:22px; font-size:10px;">{{ $mw['initials'] }}</div>
                  <span style="font-weight:600; font-size:13px;">{{ $mw['name'] }}</span>
                </div>
              </td>
              <td><span style="font-size:11.5px; color:var(--ink-soft);">{{ $mw['department'] }}</span></td>
              <td><span class="mono">{{ $mw['total'] }}</span></td>
              <td><span class="mono" style="color:var(--success); font-weight:600;">{{ $mw['done'] }}</span></td>
              <td><span class="mono" style="color:var(--accent);">{{ $mw['pending'] }}</span></td>
              <td style="min-width:90px;">
                <div style="display:flex; align-items:center; gap:6px;">
                  <div class="progressbar" style="flex:1;"><div style="width:{{ $mw['rate'] }}%"></div></div>
                  <span class="mono" style="font-size:11px;">{{ $mw['rate'] }}%</span>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Section 3: Upcoming Deadlines -->
<div class="card card-pad">
  <div class="card-title-row" style="margin-bottom:12px;">
    <h3 style="margin:0;">Upcoming Project Deadlines &amp; Deliverables</h3>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px;">
    @foreach ($upcomingDeadlines as $dl)
      @php
        $isPast = $dl->end_date && $dl->end_date->isPast();
        $daysLeft = $dl->end_date ? (int)now()->diffInDays($dl->end_date, false) : null;
      @endphp
      <div style="background:var(--bg-subtle); padding:12px; border-radius:8px; border:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
        <div>
          <a href="{{ route('projects.show', $dl) }}" style="font-weight:700; font-size:13.5px; color:var(--ink); text-decoration:none;">
            {{ $dl->project_name }}
          </a>
          <div style="font-size:11.5px; color:var(--ink-soft); margin-top:2px;">
            Target: <strong>{{ $dl->end_date->format('M d, Y') }}</strong>
          </div>
        </div>
        <div>
          @if ($isPast)
            <span class="badge b-blocked" style="font-size:11px;">Overdue</span>
          @else
            <span class="badge b-planning" style="font-size:11px;">{{ $daysLeft }} days left</span>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
