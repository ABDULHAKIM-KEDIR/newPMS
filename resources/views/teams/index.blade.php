@extends('layouts.app')
@section('title', 'Teams')
@section('crumb', '<b>Teams</b>')

@section('content')
<div class="page-head">
  <div>
    <h1>Organization Teams</h1>
    <div class="page-sub">Specialized teams, members, active workloads, and assigned projects</div>
  </div>
  @if (auth()->user()->can('manage_team') || auth()->user()->isAdmin() || auth()->user()->isDirectorOrAdmin())
    <a href="{{ route('teams.create') }}" class="btn btn-accent">+ New Team</a>
  @endif
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:18px;">
  @foreach ($teams as $t)
    @php
      $taskStats = $t->taskStats();
      $projectsCount = $t->allProjects()->count();
    @endphp
    <div class="card card-pad" style="border-top:3px solid var(--accent); display:flex; flex-direction:column; justify-content:space-between;">
      <div>
        <div class="card-title-row" style="margin-bottom:8px;">
          <h3 style="margin:0; font-size:16px;">
            <a href="{{ route('teams.show', $t) }}" style="color:var(--ink); text-decoration:none;">{{ $t->team_name }}</a>
          </h3>
          <span class="badge b-active">{{ $t->status ?: 'Active' }}</span>
        </div>

        <div style="font-size:12.5px; color:var(--ink-soft); margin-bottom:14px; line-height:1.5;">
          {{ Str::limit($t->description, 95) ?: 'Dedicated delivery team.' }}
        </div>

        <!-- Progress -->
        <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:6px; border:1px solid var(--line); margin-bottom:14px;">
          <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
            <span style="font-weight:600;">Overall Progress</span>
            <strong>{{ $taskStats['progress'] }}%</strong>
          </div>
          <div class="progressbar"><div style="width:{{ $taskStats['progress'] }}%"></div></div>
        </div>

        <div class="list-row" style="padding:6px 0; font-size:12.5px;">
          <span class="k" style="color:var(--ink-soft)">Team Lead</span>
          <span style="font-weight:600;">{{ optional($t->leader)->full_name ?? 'Unassigned' }}</span>
        </div>
        <div class="list-row" style="padding:6px 0; font-size:12.5px;">
          <span class="k" style="color:var(--ink-soft)">Staff Members</span>
          <span style="font-weight:600;">{{ $t->members->count() }} members</span>
        </div>
        <div class="list-row" style="padding:6px 0; font-size:12.5px;">
          <span class="k" style="color:var(--ink-soft)">Assigned Projects</span>
          <span style="font-weight:600;">{{ $projectsCount }} project(s)</span>
        </div>
        <div class="list-row" style="padding:6px 0; font-size:12.5px;">
          <span class="k" style="color:var(--ink-soft)">Tasks Completed</span>
          <span style="font-weight:600; color:var(--success);">{{ $taskStats['completed'] }} / {{ $taskStats['total'] }} done</span>
        </div>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:12px; border-top:1px solid var(--line);">
        <div style="display:flex; align-items:center; gap:4px;">
          @foreach ($t->members->take(4) as $tm)
            @if ($tm->user)
              <div class="avatar" style="width:24px; height:24px; font-size:9.5px;" title="{{ $tm->user->full_name }}">{{ $tm->user->initials() }}</div>
            @endif
          @endforeach
        </div>
        <a class="btn btn-ghost" href="{{ route('teams.show', $t) }}" style="font-size:12px; padding:4px 10px;">Dashboard →</a>
      </div>
    </div>
  @endforeach
</div>
@endsection
