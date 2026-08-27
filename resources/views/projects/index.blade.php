@extends('layouts.app')
@section('title', 'Projects')
@section('crumb', 'Projects')

@section('content')
<div class="page-head">
  <div>
    <h1>Projects</h1>
    <div class="page-sub">All software, network-infrastructure and training engagements run by the directorate</div>
  </div>
  @if (auth()->user()->canCreateProjects())
    <a href="{{ route('projects.create') }}" class="btn btn-accent">+ New Project</a>
  @endif
</div>

<div class="filter-row">
  <a href="{{ route('projects.index') }}" class="pill {{ !request('type') ? 'active' : '' }}">All</a>
  <a href="{{ route('projects.index', ['type' => 'Software']) }}" class="pill {{ request('type')==='Software' ? 'active' : '' }}">Software</a>
  <a href="{{ route('projects.index', ['type' => 'Network & Infrastructure']) }}" class="pill {{ request('type')==='Network & Infrastructure' ? 'active' : '' }}">Network &amp; Infrastructure</a>
  <a href="{{ route('projects.index', ['type' => 'Training & Consultancy']) }}" class="pill {{ request('type')==='Training & Consultancy' ? 'active' : '' }}">Training &amp; Consultancy</a>
</div>

<div class="card">
  <table>
    <thead><tr>
      <th style="width:24%">Project</th>
      <th>Leadership &amp; Team</th>
      <th>Phase</th>
      <th>Tasks Progress</th>
      <th>Status</th>
      <th>Budget</th>
      <th>Due</th>
    </tr></thead>
    <tbody>
      @foreach ($projects as $p)
        @php
          $b = $p->budget; $util = $b ? $b->utilisationPercent() : 0;
          $statusCls = ['active' => 'b-active', 'planning' => 'b-planning', 'risk' => 'b-risk', 'closed' => 'b-closed'][$p->status] ?? 'b-planning';
          $pTasks = $p->allTasks();
          $totalTasksCount = $pTasks->count();
          $completedTasksCount = $pTasks->filter(fn($t) => in_array($t->status, ['Done', 'Completed']))->count();
          $taskPct = $p->progressPercentage();
          $allTeams = $p->allTeams();
        @endphp
        <tr onclick="window.location='{{ route('projects.show', $p) }}'" style="cursor:pointer;">
          <td>
            <div class="cell-primary" style="display:flex; align-items:center; gap:6px;">
              <span>{{ $p->project_name }}</span>
              <span class="priority p-{{ strtolower($p->priority ?: 'medium') }}" style="font-size:9.5px;">{{ $p->priority ?: 'Medium' }}</span>
            </div>
            <div class="cell-sub mono">
              PRJ-{{ str_pad($p->project_id, 3, '0', STR_PAD_LEFT) }} · {{ $p->project_type }}
              @if ($p->client) · <span style="color:var(--accent);">🏢 {{ $p->client }}</span>@endif
            </div>
          </td>
          <td>
            @if ($p->projectManager)
              <div style="font-weight:600; font-size:12.5px; color:var(--ink); display:flex; align-items:center; gap:5px;">
                <span style="color:var(--accent); font-size:11px;">★</span> PM: {{ $p->projectManager->full_name }}
              </div>
            @endif
            <div class="cell-sub" style="margin-top:2px;">
              {{ $allTeams->pluck('team_name')->implode(', ') ?: 'No Teams' }}
            </div>
          </td>
          <td style="width:140px;">@include('partials.phase-rail', ['currentIndex' => $p->currentPhaseIndex(), 'mini' => true])</td>
          <td>
            <div class="cell-sub" style="margin-bottom:4px; font-weight:600;">
              {{ $completedTasksCount }}/{{ $totalTasksCount }} Tasks ({{ $taskPct }}%)
            </div>
            <div class="progressbar"><div style="width:{{ $taskPct }}%"></div></div>
          </td>
          <td><span class="badge {{ $statusCls }}"><span class="badge-dot"></span>{{ ucfirst($p->status) }}</span></td>
          <td>
            <div class="cell-sub" style="margin-bottom:4px;">{{ $util }}% · ETB {{ number_format($b->spent_amount ?? 0) }}</div>
            <div class="progressbar {{ $util>85?'danger':($util>65?'warn':'') }}"><div style="width:{{ $util }}%"></div></div>
          </td>
          <td>{{ optional($p->end_date)->format('d M Y') ?: 'Not set' }}</td>
        </tr>
      @endforeach
      @if ($projects->isEmpty())
        <tr>
          <td colspan="7" style="text-align:center; padding:30px; color:var(--ink-faint);">No projects found.</td>
        </tr>
      @endif
    </tbody>
  </table>
</div>

<div style="margin-top:16px;">{{ $projects->links() }}</div>
@endsection
