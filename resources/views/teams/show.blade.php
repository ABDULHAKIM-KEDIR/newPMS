@extends('layouts.app')
@section('title', $team->team_name)
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('teams.index') }}">Teams</a> <b>/ {{ $team->team_name }}</b>
@endsection

@section('content')
<div class="page-head">
  <div>
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
      <h1 style="margin:0;">{{ $team->team_name }}</h1>
      <span class="badge b-active">{{ $team->status ?: 'Active' }}</span>
    </div>
    <div class="page-sub">
      {{ $team->description ?: 'Dedicated project delivery team for university initiatives' }} ·
      Lead: <strong>{{ optional($team->leader)->full_name ?? 'Unassigned' }}</strong> ·
      {{ $team->members->count() }} Team Members ·
      {{ $allProjects->count() }} Assigned Project(s)
    </div>
  </div>
  @if (auth()->user()->canCreateProjects())
    <a href="{{ route('projects.create') }}" class="btn btn-accent">+ New Project</a>
  @endif
</div>

<!-- Team Progress Overview Metric Card -->
<div class="card card-pad" style="margin-bottom:20px; background:var(--bg-card); border:1px solid var(--line);">
  <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
    <div>
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); letter-spacing:0.5px;">Team Tasks Progress &amp; Performance</div>
      <div style="display:flex; align-items:baseline; gap:10px; margin-top:4px;">
        <span style="font-size:30px; font-weight:800; color:var(--ink);">{{ $taskStats['progress'] }}%</span>
        <span style="font-size:13px; color:var(--ink-soft);">{{ $taskStats['completed'] }} of {{ $taskStats['total'] }} team tasks completed</span>
      </div>
    </div>

    @if ($taskStats['overdue'] > 0)
      <div class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700; padding:6px 12px; font-size:12px; border:1px solid #fca5a5;">
        ⚠ {{ $taskStats['overdue'] }} Overdue Task(s)
      </div>
    @endif
  </div>

  <!-- Team Progress Bar -->
  <div style="width:100%; height:12px; background:var(--bg-subtle); border-radius:999px; overflow:hidden; margin-bottom:16px; border:1px solid var(--line);">
    <div style="width:{{ $taskStats['progress'] }}%; height:100%; background:{{ $taskStats['progress'] === 100 ? 'var(--active)' : 'var(--accent)' }}; border-radius:999px; transition:width 0.4s ease;"></div>
  </div>

  <!-- Task Status Breakdown Stats Grid -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:10px; border-top:1px solid var(--line); padding-top:12px;">
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted);">Total Tasks</div>
      <div style="font-size:18px; font-weight:800; color:var(--ink); margin-top:2px;">{{ $taskStats['total'] }}</div>
    </div>
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--success);">Completed</div>
      <div style="font-size:18px; font-weight:800; color:var(--success); margin-top:2px;">{{ $taskStats['completed'] }}</div>
    </div>
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--accent);">In Progress</div>
      <div style="font-size:18px; font-weight:800; color:var(--accent); margin-top:2px;">{{ $taskStats['in_progress'] }}</div>
    </div>
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--review);">In Review</div>
      <div style="font-size:18px; font-weight:800; color:var(--review); margin-top:2px;">{{ $taskStats['in_review'] }}</div>
    </div>
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-soft);">To Do</div>
      <div style="font-size:18px; font-weight:800; color:var(--ink-soft); margin-top:2px;">{{ $taskStats['to_do'] }}</div>
    </div>
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--danger);">Overdue</div>
      <div style="font-size:18px; font-weight:800; color:var(--danger); margin-top:2px;">{{ $taskStats['overdue'] }}</div>
    </div>
  </div>
</div>

<div class="two-col" style="margin-bottom:20px;">
  <!-- Team Members Column -->
  <div class="card card-pad">
    <div class="card-title-row">
      <h3>Team Members &amp; Workload ({{ $team->members->count() }})</h3>
      <span class="badge b-active">{{ optional($team->leader)->full_name ? 'Lead: ' . $team->leader->full_name : 'No Leader' }}</span>
    </div>

    <div style="overflow-x:auto; margin-top:8px;">
      <table>
        <thead>
          <tr>
            <th>Member</th>
            <th style="text-align:center;">Assigned</th>
            <th style="text-align:center;">In Progress</th>
            <th style="text-align:center;">Completed</th>
            <th style="text-align:center;">Overdue</th>
            <th style="text-align:center;">Blocked</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($team->members as $m)
            @php
              $u = $m->user;
              if (!$u) continue;
              $mTasks = $teamTasks->where('assigned_to', $u->user_id);
              $mAssigned = $mTasks->count();
              $mInProg = $mTasks->where('status', 'In Progress')->count();
              $mDone = $mTasks->filter(fn($t) => in_array($t->status, ['Done', 'Completed']))->count();
              $mOverdue = $mTasks->filter(fn($t) => !in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast())->count();
              $mBlocked = $mTasks->where('status', 'Blocked')->count();
            @endphp
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <div class="avatar" style="width:24px; height:24px; font-size:10px;">{{ $u->initials() }}</div>
                  <div>
                    <div style="font-weight:600; font-size:13px; display:flex; align-items:center; gap:4px;">
                      <span>{{ $u->full_name }}</span>
                      @if ($team->team_leader_id === $m->user_id)
                        <span class="badge b-planning" style="font-size:9px; padding:1px 5px;">Lead</span>
                      @endif
                    </div>
                    <div class="cell-sub" style="font-size:11px;">{{ $u->department ?: 'Staff' }}</div>
                  </div>
                </div>
              </td>
              <td style="text-align:center;"><strong class="mono">{{ $mAssigned }}</strong></td>
              <td style="text-align:center;"><span class="mono" style="color:var(--accent);">{{ $mInProg }}</span></td>
              <td style="text-align:center;"><span class="mono" style="color:var(--success);">{{ $mDone }}</span></td>
              <td style="text-align:center;">
                <span class="mono" style="{{ $mOverdue > 0 ? 'color:var(--danger); font-weight:700;' : 'color:var(--ink-muted);' }}">{{ $mOverdue }}</span>
              </td>
              <td style="text-align:center;">
                <span class="mono" style="{{ $mBlocked > 0 ? 'color:var(--danger); font-weight:700;' : 'color:var(--ink-muted);' }}">{{ $mBlocked }}</span>
              </td>
              <td style="text-align:right;">
                @if ($canManage && $team->team_leader_id !== $m->user_id)
                  <form method="POST" action="{{ route('teams.members.remove', [$team, $m]) }}" onsubmit="return confirm('Remove {{ $u->full_name }} from this team?');" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost" style="padding:3px 7px; font-size:11px; color:var(--danger);">Remove</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center; padding:20px; color:var(--ink-faint);">No members assigned yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($canManage)
      <form method="POST" action="{{ route('teams.members.add', $team) }}" style="display:flex; gap:8px; margin-top:14px; padding-top:14px; border-top:1px solid var(--line);">
        @csrf
        <input
          type="text"
          name="user_id"
          list="team-available-users-datalist"
          placeholder="Type any member name (e.g. Alex Morgan) or select..."
          required
          autocomplete="off"
          style="flex:1; border:1px solid var(--line); border-radius:6px; padding:7px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);"
        >
        <datalist id="team-available-users-datalist">
          @foreach ($availableUsers as $u)
            <option value="{{ $u->full_name }}">{{ $u->full_name }} ({{ $u->department ?: 'Staff' }})</option>
          @endforeach
        </datalist>
        <button type="submit" class="btn btn-primary" style="padding:7px 14px; font-size:12.5px;">+ Add Member</button>
      </form>

      <form method="POST" action="{{ route('teams.leader', $team) }}" style="display:flex; gap:8px; margin-top:10px;">
        @csrf
        <input
          type="text"
          name="team_leader_id"
          list="team-leader-candidates-datalist"
          placeholder="Type or select new team leader..."
          required
          autocomplete="off"
          style="flex:1; border:1px solid var(--line); border-radius:6px; padding:7px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);"
        >
        <datalist id="team-leader-candidates-datalist">
          @foreach ($leaderCandidates as $u)
            <option value="{{ $u->full_name }}">{{ $u->full_name }}</option>
          @endforeach
        </datalist>
        <button type="submit" class="btn btn-ghost" style="padding:7px 14px; font-size:12.5px;">Set Leader</button>
      </form>
    @endif
  </div>

  <!-- Assigned Projects Column -->
  <div class="card card-pad">
    <div class="card-title-row">
      <h3>Assigned Projects ({{ $allProjects->count() }})</h3>
      @if (auth()->user()->canCreateProjects())
        <a href="{{ route('projects.create') }}" class="link-small">+ New Project</a>
      @endif
    </div>

    @forelse ($allProjects as $p)
      @php
        $pProgress = $p->progressPercentage();
        $pTasks = $p->allTasks()->filter(fn($t) => (int)$t->team_id === (int)$team->team_id || (int)$p->team_id === (int)$team->team_id);
        $pTotal = $pTasks->count();
        $pDone = $pTasks->filter(fn($t) => in_array($t->status, ['Done', 'Completed']))->count();
      @endphp
      <div class="list-row" style="flex-direction:column; align-items:flex-start; gap:8px; padding:12px 0; border-bottom:1px solid var(--line);">
        <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
          <div>
            <a href="{{ route('projects.show', $p) }}" style="font-weight:700; font-size:14px; color:var(--ink); text-decoration:none;">
              {{ $p->project_name }}
            </a>
            <div class="cell-sub" style="margin-top:2px;">
              {{ $p->client ? 'Client: ' . $p->client . ' · ' : '' }}
              PM: {{ optional($p->projectManager)->full_name ?? 'Unassigned' }}
            </div>
          </div>
          <span class="badge b-active">{{ $pProgress }}% Completed</span>
        </div>

        <div style="width:100%; display:flex; justify-content:space-between; font-size:12px; color:var(--ink-soft);">
          <span>Team Tasks: <strong>{{ $pDone }} / {{ $pTotal }} done</strong></span>
          <span>Deadline: {{ optional($p->end_date)->format('M d, Y') ?: 'Not set' }}</span>
        </div>

        <div class="progressbar" style="width:100%;"><div style="width:{{ $pProgress }}%"></div></div>
      </div>
    @empty
      <div class="empty">
        <h4>No projects assigned yet</h4>
        <p style="font-size:12.5px; color:var(--ink-faint);">Projects assigned to this team will appear here.</p>
      </div>
    @endforelse
  </div>
</div>

<!-- Team Tasks Table -->
<div class="card card-pad">
  <div class="card-title-row" style="margin-bottom:12px;">
    <h3>Team Tasks &amp; Workload ({{ $teamTasks->count() }})</h3>
  </div>

  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th style="width:28%;">Task</th>
          <th>Project</th>
          <th>Assignee</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Due Date</th>
          <th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($teamTasks as $t)
          @php
            $late = !in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast();
            $proj = $t->project ?? optional($t->phase)->project;
          @endphp
          <tr onclick="openTask({{ $t->task_id }})" style="cursor:pointer;">
            <td class="cell-primary">
              {{ $t->task_name }}
              <div class="cell-sub mono">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</div>
            </td>
            <td>
              @if ($proj)
                <a href="{{ route('projects.show', $proj) }}" onclick="event.stopPropagation();" style="font-weight:600; color:var(--accent);">
                  {{ $proj->project_name }}
                </a>
              @else
                —
              @endif
            </td>
            <td>
              @if ($t->assignee)
                <div style="display:flex; align-items:center; gap:6px;">
                  <div class="avatar" style="width:22px; height:22px; font-size:10px;">{{ $t->assignee->initials() }}</div>
                  <span>{{ $t->assignee->full_name }}</span>
                </div>
              @else
                <span style="color:var(--ink-faint);">Unassigned</span>
              @endif
            </td>
            <td><span class="priority p-{{ strtolower($t->priority ?: 'medium') }}">{{ $t->priority }}</span></td>
            <td>
              <span class="badge {{ $t->statusBadgeClass() }}">
                <span class="badge-dot"></span>{{ $t->status }}
              </span>
            </td>
            <td><span class="{{ $late ? 'late' : '' }}">{{ optional($t->end_date)->format('d M Y') ?: '—' }}</span></td>
            <td style="text-align:right;">
              <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px; font-weight:600;" onclick="event.stopPropagation(); openTask({{ $t->task_id }}, true);">✎ Edit</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center; padding:24px; color:var(--ink-faint);">
              No tasks currently assigned to this team.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
