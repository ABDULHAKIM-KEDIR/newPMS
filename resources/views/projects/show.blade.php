@extends('layouts.app')
@section('title', $project->project_name)
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('projects.index') }}">Projects</a> <b>/ {{ $project->project_name }}</b>
@endsection

@section('content')
<div x-data="{ showCR: false }">
  <div class="page-head">
    <div>
      <h1>{{ $project->project_name }}</h1>
      <div class="page-sub">{{ $project->project_type }} · {{ optional($project->team)->team_name }} Team · Started {{ optional($project->start_date)->format('d M Y') }}</div>
    </div>
    <div style="display:flex; gap:8px;">
      <button class="btn btn-ghost" @click="showCR = !showCR">Log Change Request</button>
      @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()))
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">Edit Project</a>
      @endif
    </div>
  </div>

  <div class="card card-pad" x-show="showCR" x-cloak x-transition style="margin-bottom:18px;">
    <form method="POST" action="{{ route('projects.changeRequests.store', $project) }}">
      @csrf
      <div class="form-field" style="margin-bottom:12px;">
        <label for="cr_description">What's the change you're requesting?</label>
        <textarea id="cr_description" name="description" required placeholder="e.g. Extend go-live by two weeks to accommodate UAT feedback"></textarea>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-accent">Submit request</button>
        <button type="button" class="btn btn-ghost" @click="showCR = false">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div class="card card-pad" x-data="{ showPhaseManager: false }" style="margin-bottom:18px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
    <div style="display:flex; align-items:center; gap:8px;">
      <span class="stat-label" style="margin:0; font-size:13px; font-weight:600;">Project Lifecycle Phases</span>
      @php $currentPhase = $project->phases->firstWhere('status', 'In Progress') ?? $project->phases->last(); @endphp
      @if ($currentPhase)
        <span class="badge b-planning" style="font-size:11px;">Current: {{ $currentPhase->phase_name }}</span>
      @endif
    </div>
    @if ($project->isManagedBy(auth()->user()) || auth()->user()->can('edit_projects'))
      <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px;" @click="showPhaseManager = !showPhaseManager" x-text="showPhaseManager ? 'Close Phase Manager' : '⚙ Manage / Update Phases'"></button>
    @endif
  </div>

  @include('partials.phase-rail', ['currentIndex' => $project->currentPhaseIndex(), 'mini' => false, 'project' => $project])

  {{-- Phase Management & Update Panel --}}
  @if ($project->isManagedBy(auth()->user()) || auth()->user()->can('edit_projects'))
    <div x-show="showPhaseManager" x-cloak x-transition style="margin-top:16px; padding-top:16px; border-top:1px solid var(--line);">
      <h4 style="font-size:14px; margin-bottom:12px;">Update Phase Status &amp; Timeline</h4>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px; margin-bottom:16px;">
        @foreach ($project->phases as $ph)
          <div style="background:var(--surface-alt); border:1px solid var(--line); border-radius:8px; padding:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <strong style="font-size:13px;">{{ $ph->phase_name }}</strong>
              <span class="mono" style="font-size:11px; color:var(--ink-soft);">{{ $ph->tasks->count() }} task(s)</span>
            </div>
            <form method="POST" action="{{ route('phases.status', $ph) }}" style="display:flex; gap:6px; align-items:center;">
              @csrf
              <select name="status" style="flex:1; border:1px solid var(--line); border-radius:6px; padding:5px 8px; font-size:12px; font-family:inherit; background:var(--surface);">
                <option value="Not started" {{ $ph->status === 'Not started' ? 'selected' : '' }}>Not started</option>
                <option value="In Progress" {{ $ph->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                <option value="Done" {{ in_array($ph->status, ['Done', 'Completed', 'Closed']) ? 'selected' : '' }}>Done / Completed</option>
              </select>
              <button type="submit" class="btn btn-ghost" style="padding:5px 9px; font-size:11.5px;">Update</button>
            </form>
          </div>
        @endforeach
      </div>

      {{-- Add New Custom Phase Form --}}
      <div style="background:var(--surface); border:1px dashed var(--line); border-radius:8px; padding:12px;">
        <h5 style="font-size:12.5px; margin-bottom:8px; font-weight:600;">+ Add New Phase to this Project</h5>
        <form method="POST" action="{{ route('phases.store', $project) }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
          @csrf
          <input type="text" name="phase_name" required placeholder="Phase name (e.g. Deployment, UAT)" style="flex:1; min-width:180px; border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);">
          <select name="status" style="border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);">
            <option value="Not started" selected>Not started</option>
            <option value="In Progress">In Progress</option>
            <option value="Done">Done</option>
          </select>
          <button type="submit" class="btn btn-accent" style="padding:6px 14px; font-size:12.5px;">Add Phase</button>
        </form>
      </div>
    </div>
  @endif
</div>

<div x-data="{ tab: 'tasks', taskView: 'kanban' }">
  <div class="tabs">
    <div class="tab" :class="{ active: tab === 'tasks' }" @click="tab = 'tasks'">Tasks ({{ $tasks->count() }})</div>
    <div class="tab" :class="{ active: tab === 'deliverables' }" @click="tab = 'deliverables'">Deliverables ({{ $project->deliverables->count() }})</div>
    <div class="tab" :class="{ active: tab === 'budget' }" @click="tab = 'budget'">Budget</div>
    <div class="tab" :class="{ active: tab === 'changes' }" @click="tab = 'changes'">Change Requests ({{ $project->changeRequests->count() }})</div>
  </div>

  <div x-show="tab === 'tasks'">
    <!-- Task Header Toolbar & Stats -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
      <div style="display:flex; gap:8px; align-items:center;">
        <span class="stat-label" style="margin:0;">View Mode:</span>
        <button type="button" class="btn" :class="taskView === 'kanban' ? 'btn-primary' : 'btn-ghost'" @click="taskView = 'kanban'" style="padding:5px 12px; font-size:12.5px;">
          📊 Kanban Board
        </button>
        <button type="button" class="btn" :class="taskView === 'list' ? 'btn-primary' : 'btn-ghost'" @click="taskView = 'list'" style="padding:5px 12px; font-size:12.5px;">
          ☰ List View
        </button>
      </div>

      @if (auth()->user()->can('create_tasks') || $project->isManagedBy(auth()->user()))
        <div x-data="{ showNewTask: {{ $errors->any() ? 'true' : 'false' }} }" style="position:relative;">
          <button class="btn btn-accent" @click="showNewTask = !showNewTask" x-text="showNewTask ? 'Cancel' : '+ New Task'"></button>

          <div class="card card-pad" x-show="showNewTask" x-cloak x-transition style="margin-top:12px; position:absolute; right:0; z-index:100; width:480px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
              <h3 style="margin:0; font-size:15px;">Create New Task</h3>
              <button type="button" @click="showNewTask = false" style="background:none; border:none; color:var(--ink-faint); cursor:pointer; font-size:18px; line-height:1;" title="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('tasks.store') }}">
              @csrf
              <div class="form-grid">
                <div class="form-field">
                  <label for="task_name">Task name</label>
                  <input type="text" id="task_name" name="task_name" value="{{ old('task_name') }}" required autofocus placeholder="e.g. System Architecture Diagram">
                </div>
                <div class="form-field">
                  <label for="phase_id">Phase</label>
                  <select id="phase_id" name="phase_id" required>
                    @foreach ($project->phases as $ph)
                      <option value="{{ $ph->phase_id }}" {{ old('phase_id') == $ph->phase_id || (empty(old('phase_id')) && $ph->status === 'In Progress') ? 'selected' : '' }}>{{ $ph->phase_name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="form-grid">
                <div class="form-field">
                  <label for="assigned_to">Assignee</label>
                  <select id="assigned_to" name="assigned_to">
                    <option value="">— Unassigned —</option>
                    @foreach ($assignableUsers as $au)
                      <option value="{{ $au['id'] }}" {{ old('assigned_to') == $au['id'] ? 'selected' : '' }}>{{ $au['name'] }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-field">
                  <label for="priority">Priority</label>
                  <select id="priority" name="priority" required>
                    <option value="Medium" {{ old('priority', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High</option>
                    <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low</option>
                  </select>
                </div>
              </div>
              <div class="form-grid">
                <div class="form-field">
                  <label for="status">Initial Status</label>
                  <select id="status" name="status" required>
                    <option value="Pending" {{ old('status', 'Pending') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="In Progress" {{ old('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Done" {{ old('status') === 'Done' ? 'selected' : '' }}>Done</option>
                  </select>
                </div>
                <div class="form-field">
                  <label for="start_date">Start date</label>
                  <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}">
                </div>
              </div>
              <div class="form-field">
                <label for="end_date">End date</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}">
              </div>
              <div class="form-field">
                <label for="task_description">Description (optional)</label>
                <textarea id="task_description" name="description" placeholder="Brief description of the task..." style="min-height:60px;">{{ old('description') }}</textarea>
              </div>
              <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px;">
                <button type="button" class="btn btn-ghost" @click="showNewTask = false">Cancel</button>
                <button type="submit" class="btn btn-accent">Add Task</button>
              </div>
            </form>
          </div>
        </div>
      @endif
    </div>

    <!-- Kanban View -->
    <div x-show="taskView === 'kanban'">
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
                      <span class="mono" style="font-size:10px; color:var(--ink-soft); background:var(--surface-soft); padding:2px 6px; border-radius:4px;">{{ optional($t->phase)->phase_name }}</span>
                      <button type="button" class="btn btn-ghost" style="padding:2px 6px; font-size:11px; line-height:1.2;" onclick="event.stopPropagation(); openTask({{ $t->task_id }}, true);" title="Edit Task">✎ Edit</button>
                    </div>
                  </div>
                  <div class="name" style="font-weight:600; margin-bottom:8px;">{{ $t->task_name }}</div>
                  <div class="tcard-foot">
                    <span class="priority p-{{ strtolower($t->priority) }}">{{ $t->priority }}</span>
                    <div style="display:flex; align-items:center; gap:8px;">
                      <span class="duedate {{ $late ? 'late' : '' }}">{{ $late ? '⚠ ' : '' }}{{ optional($t->end_date)->format('d M') }}</span>
                      <div class="avatar" title="{{ optional($t->assignee)->full_name }}">{{ optional($t->assignee)->initials() }}</div>
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

    <!-- Table / List View -->
    <div x-show="taskView === 'list'" x-cloak>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th style="width:120px;">Task ID</th>
              <th>Task Name</th>
              <th>Phase</th>
              <th>Assignee</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($tasks as $t)
              @php $late = $t->status !== 'Done' && $t->end_date && $t->end_date->isPast(); @endphp
              <tr onclick="openTask({{ $t->task_id }})" style="cursor:pointer;">
                <td class="mono cell-sub">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</td>
                <td class="cell-primary">{{ $t->task_name }}</td>
                <td>{{ optional($t->phase)->phase_name }}</td>
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
                  <span class="badge {{ $t->status === 'Done' ? 'b-active' : ($t->status === 'In Progress' ? 'b-planning' : 'b-risk') }}">
                    <span class="badge-dot"></span>{{ $t->status }}
                  </span>
                </td>
                <td><span class="cell-sub">{{ optional($t->start_date)->format('d M Y') ?: '—' }}</span></td>
                <td><span class="{{ $late ? 'late' : '' }}">{{ optional($t->end_date)->format('d M Y') ?: '—' }}</span></td>
                <td style="text-align:right;">
                  <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px; font-weight:600;" onclick="event.stopPropagation(); openTask({{ $t->task_id }}, true);">✎ Edit</button>
                </td>
              </tr>
            @endforeach
            @if ($tasks->isEmpty())
              <tr>
                <td colspan="9" style="text-align:center; padding:30px; color:var(--ink-faint);">No tasks found for this project.</td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>


  <div x-show="tab === 'deliverables'" x-cloak>
    <div class="card"><table>
      <thead><tr><th style="width:40%">Deliverable</th><th>Due date</th><th>Status</th></tr></thead>
      <tbody>
        @foreach ($project->deliverables as $d)
          <tr>
            <td class="cell-primary">{{ $d->deliverable_name }}</td>
            <td>{{ optional($d->due_date)->format('d M Y') }}</td>
            <td><span class="badge {{ $d->status === 'Delivered' ? 'b-active' : 'b-planning' }}"><span class="badge-dot"></span>{{ $d->status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  </div>

  <div x-show="tab === 'budget'" x-cloak>
    @php $b = $project->budget; $util = $b ? $b->utilisationPercent() : 0; @endphp
    <div class="grid grid-3">
      <div class="card card-pad">
        <div class="card-title-row"><h3>Budget by phase</h3></div>
        @foreach ($project->phases as $ph)
          @php $pb = $ph->budget; $pct = $pb && $pb->allocated_amount > 0 ? round($pb->spent_amount / $pb->allocated_amount * 100) : 0; @endphp
          <div style="margin-bottom:13px;">
            <div style="display:flex; justify-content:space-between; font-size:12.6px; margin-bottom:5px;">
              <span>{{ $ph->phase_name }}</span><span class="mono" style="color:var(--ink-soft)">ETB {{ number_format($pb->allocated_amount ?? 0) }}</span>
            </div>
            <div class="progressbar {{ $pct>85?'danger':($pct>65?'warn':'') }}"><div style="width:{{ $pct }}%"></div></div>
          </div>
        @endforeach
      </div>
      <div class="card card-pad">
        <div class="stat-label">Total allocated</div>
        <div class="stat-value">ETB {{ number_format($b->allocated_amount ?? 0) }}</div>
        <div style="margin:14px 0 6px;" class="stat-label">Spent — {{ $util }}%</div>
        <div class="progressbar"><div style="width:{{ $util }}%"></div></div>
        <div class="stat-delta" style="margin-top:16px;">ETB {{ number_format($b->spent_amount ?? 0) }} spent</div>
      </div>
    </div>
  </div>

  <div x-show="tab === 'changes'" x-cloak>
    <div class="card"><table>
      <thead><tr><th style="width:34%">Request</th><th>Requested by</th><th>Date</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach ($project->changeRequests as $c)
          @php $ccls = ['Approved' => 'b-active', 'Rejected' => 'b-blocked'][$c->status] ?? 'b-risk'; @endphp
          <tr>
            <td class="cell-primary">{{ $c->description }}</td>
            <td>{{ optional($c->requester)->full_name }}</td>
            <td>{{ optional($c->requested_date)->format('d M Y') }}</td>
            <td><span class="badge {{ $ccls }}"><span class="badge-dot"></span>{{ $c->status }}</span></td>
            <td style="text-align:right;">
              @if ($c->status === 'Pending' && auth()->user()->can('approve_change_requests'))
                <form method="POST" action="{{ route('changeRequests.approve', $c) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="btn btn-ghost" style="padding:5px 11px; font-size:11.5px; color:var(--success); border-color:var(--success-soft);">Approve</button>
                </form>
                <form method="POST" action="{{ route('changeRequests.reject', $c) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="btn btn-ghost" style="padding:5px 11px; font-size:11.5px; color:var(--danger); border-color:var(--danger-soft);">Reject</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  </div>
</div>
@endsection
