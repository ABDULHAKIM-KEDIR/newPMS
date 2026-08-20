@extends('layouts.app')
@section('title', $filter === 'all' ? 'All Tasks' : 'My Tasks')
@section('crumb', '<b>' . ($filter === 'all' ? 'All Tasks' : 'My Tasks') . '</b>')

@section('content')
<div x-data="{ viewMode: 'list' }">
  <div class="page-head">
    <div>
      <h1>{{ $filter === 'all' ? 'All Tasks' : 'My Tasks' }}</h1>
      <div class="page-sub">{{ $filter === 'all' ? 'All tasks across all directorate projects' : 'Everything assigned to you, across every project' }}</div>
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
      <span class="stat-label" style="margin:0;">View:</span>
      <button type="button" class="btn" :class="viewMode === 'kanban' ? 'btn-primary' : 'btn-ghost'" @click="viewMode = 'kanban'" style="padding:5px 12px; font-size:12.5px;">
        📊 Kanban
      </button>
      <button type="button" class="btn" :class="viewMode === 'list' ? 'btn-primary' : 'btn-ghost'" @click="viewMode = 'list'" style="padding:5px 12px; font-size:12.5px;">
        ☰ List
      </button>
    </div>
  </div>

  <div class="filter-row" style="margin-bottom:18px;">
    <a href="{{ route('tasks.index', array_filter(['filter' => 'mine', 'status' => $status, 'q' => $search])) }}" class="pill {{ $filter !== 'all' ? 'active' : '' }}">My Tasks ({{ $myCount }})</a>
    @can('view_projects')
      <a href="{{ route('tasks.index', array_filter(['filter' => 'all', 'status' => $status, 'q' => $search])) }}" class="pill {{ $filter === 'all' ? 'active' : '' }}">All Tasks ({{ $allCount }})</a>
    @endcan

    <div style="height:18px; width:1px; background:var(--line); margin:0 4px;"></div>

    <a href="{{ route('tasks.index', array_filter(['filter' => $filter, 'q' => $search])) }}" class="pill {{ empty($status) ? 'active' : '' }}">All Statuses</a>
    <a href="{{ route('tasks.index', array_filter(['filter' => $filter, 'status' => 'Pending', 'q' => $search])) }}" class="pill {{ $status === 'Pending' ? 'active' : '' }}">Pending</a>
    <a href="{{ route('tasks.index', array_filter(['filter' => $filter, 'status' => 'In Progress', 'q' => $search])) }}" class="pill {{ $status === 'In Progress' ? 'active' : '' }}">In Progress</a>
    <a href="{{ route('tasks.index', array_filter(['filter' => $filter, 'status' => 'Done', 'q' => $search])) }}" class="pill {{ $status === 'Done' ? 'active' : '' }}">Done</a>
  </div>

  <!-- Kanban View -->
  <div x-show="viewMode === 'kanban'" x-cloak>
    <div class="kanban">
      @foreach (['Pending' => 'Pending', 'In Progress' => 'In Progress', 'Done' => 'Done'] as $statusKey => $statusLabel)
        <div>
          <div class="kcol-head">
            <h4>{{ $statusLabel }}</h4>
            <span class="kcol-count">{{ $tasks->where('status', $statusKey)->count() }}</span>
          </div>
          <div class="kcol-body">
            @foreach ($tasks->where('status', $statusKey) as $t)
              @php $late = $t->status !== 'Done' && $t->end_date && $t->end_date->isPast(); @endphp
              <div class="tcard" onclick="openTask({{ $t->task_id }})">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <span class="id mono">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</span>
                  <div style="display:flex; align-items:center; gap:6px;">
                    <span class="mono" style="font-size:10px; color:var(--ink-soft); background:var(--surface-soft); padding:2px 6px; border-radius:4px;">{{ optional(optional($t->phase)->project)->project_name }}</span>
                    <button type="button" class="btn btn-ghost" style="padding:2px 6px; font-size:11px; line-height:1.2;" onclick="event.stopPropagation(); openTask({{ $t->task_id }}, true);" title="Edit Task">✎ Edit</button>
                  </div>
                </div>
                <div class="name" style="font-weight:600; margin-bottom:8px;">{{ $t->task_name }}</div>
                <div class="tcard-foot">
                  <span class="priority p-{{ strtolower($t->priority) }}">{{ $t->priority }}</span>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <span class="duedate {{ $late ? 'late' : '' }}">{{ $late ? '⚠ ' : '' }}{{ optional($t->end_date)->format('d M') }}</span>
                    @if ($t->assignee)
                      <div class="avatar" title="{{ $t->assignee->full_name }}">{{ $t->assignee->initials() }}</div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
            @if ($tasks->where('status', $statusKey)->isEmpty())
              <div style="text-align:center; padding:24px 8px; color:var(--ink-faint); font-size:12.5px;">No tasks in {{ strtolower($statusLabel) }}</div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <!-- List View -->
  <div x-show="viewMode === 'list'">
    <div class="card">
      <table>
        <thead>
          <tr>
            <th style="width:28%">Task</th>
            <th>Project</th>
            <th>Phase</th>
            <th>Assignee</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Due</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($tasks as $t)
            @php $late = $t->status !== 'Done' && $t->end_date && $t->end_date->isPast(); @endphp
            <tr onclick="openTask({{ $t->task_id }})" style="cursor:pointer;">
              <td class="cell-primary">
                {{ $t->task_name }}
                <div class="cell-sub mono">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</div>
              </td>
              <td>
                @if (optional($t->phase)->project)
                  <a href="{{ route('projects.show', $t->phase->project) }}" onclick="event.stopPropagation();" style="font-weight:600;">
                    {{ $t->phase->project->project_name }}
                  </a>
                @else
                  —
                @endif
              </td>
              <td>{{ optional($t->phase)->phase_name ?? '—' }}</td>
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
              <td><span class="priority p-{{ strtolower($t->priority) }}">{{ $t->priority }}</span></td>
              <td>
                @php $cls = ['Done' => 'b-active', 'In Progress' => 'b-planning'][$t->status] ?? 'b-risk'; @endphp
                <span class="badge {{ $cls }}"><span class="badge-dot"></span>{{ $t->status }}</span>
              </td>
              <td><span class="{{ $late ? 'late' : '' }}">{{ optional($t->end_date)->format('d M Y') ?: '—' }}</span></td>
              <td style="text-align:right;">
                <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px; font-weight:600;" onclick="event.stopPropagation(); openTask({{ $t->task_id }}, true);">✎ Edit</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8">
                <div class="empty">
                  <h4>No tasks found</h4>
                  <p>{{ $filter === 'all' ? 'No tasks match the selected criteria.' : 'Tasks assigned to you will show up here.' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
