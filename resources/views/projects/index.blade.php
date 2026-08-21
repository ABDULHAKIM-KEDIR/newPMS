@extends('layouts.app')
@section('title', 'Projects')
@section('crumb', '<b>Projects</b>')

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
      <th style="width:26%">Project</th>
      <th>Team</th>
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
          $allTasks = collect($p->phases)->flatMap->tasks;
          $totalTasksCount = $allTasks->count();
          $completedTasksCount = $allTasks->where('status', 'Done')->count();
          $taskPct = $totalTasksCount > 0 ? round(($completedTasksCount / $totalTasksCount) * 100) : 0;
        @endphp
        <tr onclick="window.location='{{ route('projects.show', $p) }}'" style="cursor:pointer;">
          <td>
            <div class="cell-primary">{{ $p->project_name }}</div>
            <div class="cell-sub mono">PRJ-{{ str_pad($p->project_id, 3, '0', STR_PAD_LEFT) }} · {{ $p->project_type }}</div>
          </td>
          <td>{{ optional($p->team)->team_name }}</td>
          <td style="width:140px;">@include('partials.phase-rail', ['currentIndex' => $p->currentPhaseIndex(), 'mini' => true])</td>
          <td>
            <div class="cell-sub" style="margin-bottom:4px; font-weight:600;">
              {{ $completedTasksCount }}/{{ $totalTasksCount }} Tasks Done ({{ $taskPct }}%)
            </div>
            <div class="progressbar"><div style="width:{{ $taskPct }}%"></div></div>
          </td>
          <td><span class="badge {{ $statusCls }}"><span class="badge-dot"></span>{{ ucfirst($p->status) }}</span></td>
          <td>
            <div class="cell-sub" style="margin-bottom:4px;">{{ $util }}% · ETB {{ number_format($b->spent_amount ?? 0) }}</div>
            <div class="progressbar {{ $util>85?'danger':($util>65?'warn':'') }}"><div style="width:{{ $util }}%"></div></div>
          </td>
          <td>{{ optional($p->end_date)->format('d M Y') }}</td>
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
