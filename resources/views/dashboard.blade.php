@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb', '<b>Dashboard</b>')

@section('content')
<div class="page-head">
  <div>
    <h1>Good morning, {{ explode(' ', auth()->user()->full_name)[0] }} 👋</h1>
    <div class="page-sub">Directorate-wide status across {{ $stats['active_projects'] }} active projects · {{ now()->format('l, j M Y') }}</div>
  </div>
  <a href="{{ route('projects.create') }}" class="btn btn-accent">+ New Project</a>
</div>

<div class="grid grid-4" style="margin-bottom:18px;">
  <div class="card stat-card">
    <div class="stat-label">Active Projects</div>
    <div class="stat-value">{{ $stats['active_projects'] }}</div>
    <div class="stat-delta">Across all teams</div>
  </div>
  <div class="card stat-card">
    <div class="stat-label">Open Tasks</div>
    <div class="stat-value">{{ $stats['open_tasks'] }}</div>
    <div class="stat-delta down">{{ $stats['overdue_tasks'] }} overdue</div>
  </div>
  <div class="card stat-card">
    <div class="stat-label">Budget Utilised</div>
    @php $util = $stats['budget_allocated'] > 0 ? round($stats['budget_spent'] / $stats['budget_allocated'] * 100) : 0; @endphp
    <div class="stat-value">{{ $util }}%</div>
    <div class="stat-delta">ETB {{ number_format($stats['budget_spent']) }} of {{ number_format($stats['budget_allocated']) }}</div>
  </div>
  <div class="card stat-card">
    <div class="stat-label">Pending Change Requests</div>
    <div class="stat-value">{{ $stats['pending_change_requests'] }}</div>
    <div class="stat-delta down">Awaiting approval</div>
  </div>
</div>

<div class="two-col">
  <div class="card card-pad">
    <div class="card-title-row">
      <h3>Projects in progress</h3>
      <a class="link-small" href="{{ route('projects.index') }}">View all →</a>
    </div>
    <table><tbody>
      @foreach ($projects as $p)
        @php $b = $p->budget; $util = $b ? $b->utilisationPercent() : 0; @endphp
        <tr onclick="window.location='{{ route('projects.show', $p) }}'">
          <td>
            <div class="cell-primary">{{ $p->project_name }}</div>
            <div class="cell-sub mono">PRJ-{{ str_pad($p->project_id, 3, '0', STR_PAD_LEFT) }} · {{ $p->project_type }}</div>
          </td>
          <td>{{ optional($p->team)->team_name }}</td>
          <td style="width:150px;">@include('partials.phase-rail', ['currentIndex' => $p->currentPhaseIndex(), 'mini' => true])</td>
          <td>
            <div class="cell-sub" style="margin-bottom:4px;">{{ $util }}%</div>
            <div class="progressbar {{ $util>85?'danger':($util>65?'warn':'') }}"><div style="width:{{ $util }}%"></div></div>
          </td>
        </tr>
      @endforeach
    </tbody></table>
  </div>

  <div style="display:flex; flex-direction:column; gap:16px;">
    <div class="card card-pad">
      <div class="card-title-row"><h3>Recent activity</h3></div>
      @foreach ($activity as $a)
        <div class="activity-row">
          <div class="activity-icon">📝</div>
          <div>
            <div class="activity-txt"><b>{{ optional($a->user)->full_name ?? 'System' }}</b> {{ $a->action }} — {{ $a->entity_type }}</div>
            <div class="activity-time">{{ $a->timestamp->diffForHumans() }}</div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="card card-pad">
      <div class="card-title-row"><h3>Team load — open tasks</h3></div>
      @foreach ($teamLoad as $u)
        @php $pct = min(100, $u->open_task_count * 15); @endphp
        <div style="margin-bottom:13px;">
          <div style="display:flex; justify-content:space-between; font-size:12.6px; margin-bottom:5px;">
            <span>{{ $u->full_name }}</span><span class="mono" style="color:var(--ink-soft)">{{ $u->open_task_count }}</span>
          </div>
          <div class="progressbar {{ $pct>=95?'danger':($pct>=80?'warn':'') }}"><div style="width:{{ $pct }}%"></div></div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
