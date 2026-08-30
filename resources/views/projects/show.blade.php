@extends('layouts.app')
@section('title', $project->project_name)
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('projects.index') }}">Projects</a> <b>/ {{ $project->project_name }}</b>
@endsection

@section('content')
@php
  $progressPct = $project->progressPercentage();
  $allTeams = $project->allTeams();
@endphp

<div x-data="{ showCR: false, showAssignTeamModal: false }">
  <!-- Project Header -->
  <div class="page-head">
    <div>
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
        <span class="mono" style="font-weight:700; color:var(--ink-soft); font-size:12px; background:var(--surface-soft); padding:2px 8px; border-radius:4px;">
          PRJ-{{ str_pad($project->project_id, 3, '0', STR_PAD_LEFT) }}
        </span>
        @if ($project->client)
          <span class="badge" style="background:var(--primary-soft); color:var(--primary-dark); font-weight:700; font-size:11px;">
            🏢 {{ $project->client }}
          </span>
        @endif
        <span class="priority p-{{ strtolower($project->priority ?: 'medium') }}" style="font-size:11px;">
          {{ $project->priority ?: 'Medium' }} Priority
        </span>
        <x-status-badge :status="$project->status" class="badge-sm" />
      </div>

      <h1 style="margin:0 0 4px; font-size:24px; font-weight:800;">{{ $project->project_name }}</h1>
      <div class="page-sub" style="font-size:13px; color:var(--ink-soft);">
        @if ($project->projectManager)
          Manager: <strong>{{ $project->projectManager->full_name }}</strong> ·
        @endif
        {{ $allTeams->count() }} Team(s) Assigned ·
        Deadline: <strong>{{ optional($project->end_date)->format('M d, Y') ?: 'Not set' }}</strong>
      </div>
    </div>

    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <button class="btn btn-ghost" @click="showCR = !showCR">Log Change Request</button>
      @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()))
        <button class="btn btn-ghost" @click="showAssignTeamModal = true">+ Assign Team</button>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">Edit Project</a>
      @endif
    </div>
  </div>

  <!-- Change Request Slide Form -->
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

  <!-- Assign Team Modal -->
  <template x-if="showAssignTeamModal">
    <div>
      <div class="overlay show" @click="showAssignTeamModal = false"></div>
      <div class="card card-pad" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1000; width:440px; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
          <h3 style="margin:0; font-size:16px; font-weight:700;">Assign Team to Project</h3>
          <button type="button" @click="showAssignTeamModal = false" style="background:none; border:none; font-size:18px; cursor:pointer; color:var(--ink-faint);">&times;</button>
        </div>
        <form method="POST" action="{{ route('projects.teams.assign', $project) }}">
          @csrf
          <div class="form-field">
            <label for="modal_team_id">Select Team to Assign</label>
            <select id="modal_team_id" name="team_id" required>
              <option value="">— Select Team —</option>
              @foreach (\App\Models\Team::where('status', 'Active')->orderBy('team_name')->get() as $tm)
                <option value="{{ $tm->team_id }}">{{ $tm->team_name }} (Lead: {{ optional($tm->leader)->full_name ?? 'Unassigned' }})</option>
              @endforeach
            </select>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
            <button type="button" class="btn btn-ghost" @click="showAssignTeamModal = false">Cancel</button>
            <button type="submit" class="btn btn-accent">Assign Team</button>
          </div>
        </form>
      </div>
    </div>
  </template>
</div>

<!-- Project Dashboard Overview Metric Card -->
<div class="card card-pad" style="margin-bottom:20px; background:var(--bg-card); border:1px solid var(--line);">
  <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; flex-wrap:wrap; gap:12px;">
    <div>
      <div style="font-size:12px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); letter-spacing:0.5px;">Overall Project Progress</div>
      <div style="display:flex; align-items:baseline; gap:10px; margin-top:4px;">
        <span style="font-size:32px; font-weight:800; color:var(--ink);">{{ $progressPct }}%</span>
        <span style="font-size:13px; color:var(--ink-soft);">{{ $taskStats['completed'] }} of {{ $taskStats['total'] }} tasks completed</span>
      </div>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
      @if ($taskStats['overdue'] > 0)
        <div class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700; padding:6px 12px; font-size:12px; border:1px solid #fca5a5;">
          ⚠ {{ $taskStats['overdue'] }} Overdue Task(s)
        </div>
      @endif
      <div class="badge b-planning" style="padding:6px 12px; font-size:12px; font-weight:600;">
        📅 Deadline: {{ optional($project->end_date)->format('M d, Y') ?: 'Not set' }}
      </div>
    </div>
  </div>

  <!-- Progress Bar -->
  <div style="width:100%; height:12px; background:var(--bg-subtle); border-radius:999px; overflow:hidden; margin-bottom:18px; border:1px solid var(--line);">
    <div style="width:{{ $progressPct }}%; height:100%; background:{{ $progressPct === 100 ? 'var(--active)' : 'var(--accent)' }}; border-radius:999px; transition:width 0.4s ease;"></div>
  </div>

  <!-- Metrics Grid -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:12px; border-top:1px solid var(--line); padding-top:14px;">
    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted);">Teams</div>
      <div style="font-size:19px; font-weight:800; color:var(--ink); margin-top:2px;">{{ $allTeams->count() }}</div>
    </div>

    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted);">Total Tasks</div>
      <div style="font-size:19px; font-weight:800; color:var(--ink); margin-top:2px;">{{ $taskStats['total'] }}</div>
    </div>

    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--success);">Completed</div>
      <div style="font-size:19px; font-weight:800; color:var(--success); margin-top:2px;">{{ $taskStats['completed'] }}</div>
    </div>

    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--accent);">In Progress</div>
      <div style="font-size:19px; font-weight:800; color:var(--accent); margin-top:2px;">{{ $taskStats['in_progress'] }}</div>
    </div>

    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-soft);">To Do</div>
      <div style="font-size:19px; font-weight:800; color:var(--ink-soft); margin-top:2px;">{{ $taskStats['to_do'] }}</div>
    </div>

    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--review);">In Review</div>
      <div style="font-size:19px; font-weight:800; color:var(--review); margin-top:2px;">{{ $taskStats['in_review'] }}</div>
    </div>

    <div style="background:var(--bg-subtle); padding:10px 12px; border-radius:8px; border:1px solid var(--line);">
      <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--danger);">Overdue</div>
      <div style="font-size:19px; font-weight:800; color:var(--danger); margin-top:2px;">{{ $taskStats['overdue'] }}</div>
    </div>
  </div>
</div>

<div x-data="{
    tab: 'tasks',
    taskView: 'kanban',
    editBudgetModal: false,
    selectedPhaseId: null,
    phaseAllocated: 0,
    phaseSpent: 0
}">
  <div class="tabs">
    <div class="tab" :class="{ active: tab === 'tasks' }" @click="tab = 'tasks'">Tasks ({{ $tasks->count() }})</div>
    <div class="tab" :class="{ active: tab === 'teams' }" @click="tab = 'teams'">Assigned Teams ({{ $allTeams->count() }})</div>
    <div class="tab" :class="{ active: tab === 'roster' }" @click="tab = 'roster'">Roster &amp; Specialists ({{ $projectRoster->count() }})</div>
    <div class="tab" :class="{ active: tab === 'phases' }" @click="tab = 'phases'">Phases ({{ $project->phases->count() }})</div>
    <div class="tab" :class="{ active: tab === 'budget' }" @click="tab = 'budget'">Budget</div>
    <div class="tab" :class="{ active: tab === 'deliverables' }" @click="tab = 'deliverables'">Deliverables ({{ $project->deliverables->count() }})</div>
    <div class="tab" :class="{ active: tab === 'changes' }" @click="tab = 'changes'">Change Requests ({{ $project->changeRequests->count() }})</div>
  </div>

  <!-- TASKS TAB -->
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
              <input type="hidden" name="project_id" value="{{ $project->project_id }}">

              <div class="form-grid">
                <div class="form-field" style="grid-column:1 / -1;">
                  <label for="task_name">Task Name <span style="color:var(--danger);">*</span></label>
                  <input type="text" id="task_name" name="task_name" value="{{ old('task_name') }}" required autofocus placeholder="e.g. System Architecture Diagram">
                </div>

                <div class="form-field">
                  <label for="team_id">Assigned Team</label>
                  <select id="team_id" name="team_id">
                    <option value="">— Select Team —</option>
                    @foreach ($allTeams as $tm)
                      <option value="{{ $tm->team_id }}">{{ $tm->team_name }}</option>
                    @endforeach
                  </select>
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
                  <input
                    type="text"
                    id="assigned_to"
                    name="assigned_to"
                    list="task-assignees-datalist"
                    value="{{ old('assigned_to') }}"
                    placeholder="Type or select member..."
                    autocomplete="off"
                  >
                  <datalist id="task-assignees-datalist">
                    @foreach ($assignableUsers as $au)
                      <option value="{{ $au['raw_name'] }}">{{ $au['name'] }}</option>
                    @endforeach
                  </datalist>
                </div>

                <div class="form-field">
                  <label for="priority">Priority</label>
                  <select id="priority" name="priority" required>
                    <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High</option>
                    <option value="Medium" {{ old('priority', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low</option>
                    <option value="Urgent" {{ old('priority') === 'Urgent' ? 'selected' : '' }}>Urgent</option>
                  </select>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-field">
                  <label for="status">Initial Status</label>
                  <select id="status" name="status" required>
                    <option value="To Do" selected>To Do</option>
                    <option value="In Progress">In Progress</option>
                    <option value="In Review">In Review</option>
                    <option value="Completed">Completed</option>
                    <option value="Blocked">Blocked</option>
                  </select>
                </div>
                <div class="form-field">
                  <label for="modal_task_budget">Task Budget (ETB)</label>
                  <input type="number" step="0.01" min="0" id="modal_task_budget" name="budget" value="{{ old('budget') }}" placeholder="e.g. 20000">
                </div>
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

    <!-- Kanban View (5 Columns with HTML5 Drag & Drop) -->
    <div x-show="taskView === 'kanban'">
      <div class="kanban" style="display:grid; grid-template-columns:repeat(5, minmax(200px, 1fr)); gap:12px; align-items:start; overflow-x:auto;">
        @php
          $projColumns = [
            'To Do' => ['label' => 'TO DO', 'match' => ['To Do', 'Pending', 'Not started']],
            'In Progress' => ['label' => 'IN PROGRESS', 'match' => ['In Progress']],
            'In Review' => ['label' => 'IN REVIEW', 'match' => ['In Review']],
            'Completed' => ['label' => 'COMPLETED', 'match' => ['Completed', 'Done']],
            'Blocked' => ['label' => 'BLOCKED', 'match' => ['Blocked']],
          ];
        @endphp

        @foreach ($projColumns as $statusKey => $col)
          @php
            $colTasks = $tasks->filter(fn($t) => in_array($t->status, $col['match']));
          @endphp
          <div
            class="kcol"
            id="proj-kanban-col-{{ Str::slug($statusKey) }}"
            ondragover="handleProjDragOver(event)"
            ondrop="handleProjDrop(event, '{{ $statusKey }}')"
            style="background:var(--bg-subtle); border:1px solid var(--line); border-radius:8px; padding:10px; min-height:400px;"
          >
            <div class="kcol-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid var(--line);">
              <h4 style="margin:0; font-size:12px; font-weight:700; text-transform:uppercase; color:var(--ink);">{{ $col['label'] }}</h4>
              <span class="kcol-count badge" id="proj-count-{{ Str::slug($statusKey) }}" style="font-size:11px;">{{ $colTasks->count() }}</span>
            </div>

            <div class="kcol-body" id="proj-tasks-{{ Str::slug($statusKey) }}" style="display:flex; flex-direction:column; gap:8px;">
              @foreach ($colTasks as $t)
                @php $late = !in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast(); @endphp
                <div
                  class="tcard"
                  id="proj-task-card-{{ $t->task_id }}"
                  draggable="true"
                  ondragstart="handleProjDragStart(event, {{ $t->task_id }})"
                  onclick="openTask({{ $t->task_id }})"
                  style="background:var(--bg-card); border:1px solid var(--line); border-radius:6px; padding:10px; cursor:grab;"
                >
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span class="id mono" style="font-size:10px; font-weight:700; color:var(--ink-muted);">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</span>
                    @if ($t->team)
                      <span class="badge b-active" style="font-size:9px; padding:1px 5px;">{{ $t->team->team_name }}</span>
                    @endif
                  </div>
                  <div class="name" style="font-weight:600; font-size:13px; margin-bottom:6px;">{{ $t->task_name }}</div>
                  <div class="tcard-foot" style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; padding-top:6px; border-top:1px solid var(--line);">
                    <span class="priority p-{{ strtolower($t->priority ?: 'medium') }}" style="font-size:10.5px;">{{ $t->priority }}</span>
                    <div style="display:flex; align-items:center; gap:6px;">
                      @if ($t->comments->count() > 0)
                        <span style="font-size:10.5px; color:var(--ink-soft);">💬 {{ $t->comments->count() }}</span>
                      @endif
                      <span class="duedate {{ $late ? 'late' : '' }}" style="font-size:10.5px;">
                        {{ $late ? '⚠ ' : '' }}{{ optional($t->end_date)->format('d M') ?: '—' }}
                      </span>
                      @if ($t->assignee)
                        <div class="avatar" style="width:20px; height:20px; font-size:9px;" title="{{ $t->assignee->full_name }}">{{ $t->assignee->initials() }}</div>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <!-- List View -->
    <div x-show="taskView === 'list'" x-cloak>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th style="width:110px;">Task ID</th>
              <th>Task Name</th>
              <th>Team</th>
              <th>Phase</th>
              <th>Assignee</th>
              <th>Priority</th>
              <th>Budget</th>
              <th>Status</th>
              <th>Due</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($tasks as $t)
              @php $late = !in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast(); @endphp
              <tr onclick="openTask({{ $t->task_id }})" style="cursor:pointer;">
                <td class="mono cell-sub">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</td>
                <td class="cell-primary">{{ $t->task_name }}</td>
                <td><span class="badge b-planning" style="font-size:11px;">{{ optional($t->team)->team_name ?? '—' }}</span></td>
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
                <td><span class="mono" style="font-weight:600; font-size:12px; color:var(--ink);">ETB {{ number_format($t->budget ?: 0) }}</span></td>
                <td>
                  <x-status-badge :status="$t->status" />
                </td>
                <td><span class="{{ $late ? 'late' : '' }}">{{ optional($t->end_date)->format('d M Y') ?: '—' }}</span></td>
                <td style="text-align:right;">
                  <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px; font-weight:600;" onclick="event.stopPropagation(); openTask({{ $t->task_id }}, true);">✎ Edit</button>
                </td>
              </tr>
            @endforeach
            @if ($tasks->isEmpty())
              <tr>
                <td colspan="10" style="text-align:center; padding:30px; color:var(--ink-faint);">No tasks found for this project.</td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ASSIGNED TEAMS TAB -->
  <div x-show="tab === 'teams'" x-cloak>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <div>
        <h3 style="margin:0; font-size:16px; font-weight:700;">Participating Teams ({{ $allTeams->count() }})</h3>
        <p style="font-size:12.5px; color:var(--ink-soft); margin:3px 0 0;">Teams assigned to deliver this project and their task progress.</p>
      </div>
      @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()))
        <button class="btn btn-accent" @click="showAssignTeamModal = true">+ Assign Another Team</button>
      @endif
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px;">
      @foreach ($allTeams as $assignedTeam)
        @php
          $teamProjectTasks = $tasks->where('team_id', $assignedTeam->team_id);
          $teamDone = $teamProjectTasks->filter(fn($t) => in_array($t->status, ['Done', 'Completed']))->count();
          $teamTotal = $teamProjectTasks->count();
          $teamPct = $teamTotal > 0 ? round(($teamDone / $teamTotal) * 100) : 0;
        @endphp
        <div class="card card-pad" style="border-top:3px solid var(--accent);">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div>
              <a href="{{ route('teams.show', $assignedTeam) }}" style="font-size:16px; font-weight:700; color:var(--ink); text-decoration:none;">
                {{ $assignedTeam->team_name }}
              </a>
              <div style="font-size:12px; color:var(--ink-soft); margin-top:2px;">
                Lead: <strong>{{ optional($assignedTeam->leader)->full_name ?? 'Unassigned' }}</strong>
              </div>
            </div>
            <span class="badge b-active">{{ $assignedTeam->members->count() }} members</span>
          </div>

          <div style="font-size:12.5px; color:var(--ink-soft); margin-bottom:14px; line-height:1.4;">
            {{ Str::limit($assignedTeam->description, 100) ?: 'No team description.' }}
          </div>

          <!-- Progress for this project -->
          <div style="margin-bottom:14px; background:var(--bg-subtle); padding:10px 12px; border-radius:6px; border:1px solid var(--line);">
            <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:6px;">
              <span style="font-weight:600;">Project Tasks</span>
              <strong>{{ $teamDone }} / {{ $teamTotal }} completed ({{ $teamPct }}%)</strong>
            </div>
            <div class="progressbar"><div style="width:{{ $teamPct }}%"></div></div>
          </div>

          <!-- Team Members Avatars -->
          <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--line); padding-top:10px;">
            <div style="display:flex; align-items:center; gap:4px; flex-wrap:wrap;">
              @foreach ($assignedTeam->members->take(5) as $tm)
                @if ($tm->user)
                  <div class="avatar" style="width:24px; height:24px; font-size:10px;" title="{{ $tm->user->full_name }}">{{ $tm->user->initials() }}</div>
                @endif
              @endforeach
            </div>

            @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()) && $allTeams->count() > 1)
              <form method="POST" action="{{ route('projects.teams.remove', [$project, $assignedTeam]) }}" onsubmit="return confirm('Remove team {{ $assignedTeam->team_name }} from this project?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost" style="padding:3px 8px; font-size:11px; color:var(--danger);">Unassign</button>
              </form>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <!-- ROSTER & SPECIALISTS TAB -->
  <div x-show="tab === 'roster'" x-cloak>
    <div class="card card-pad" style="margin-bottom:20px;">
      <div class="card-title-row" style="align-items:center;">
        <div>
          <h3 style="margin:0;">Project Roster &amp; Assigned Roles ({{ $projectRoster->count() }})</h3>
          <div style="font-size:12px; color:var(--ink-soft); margin-top:2px;">
            All managers, team leads, members, and specialists assigned to this project
          </div>
        </div>
      </div>

      <div style="margin-top:14px; overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th style="width:30%;">Person</th>
              <th>Team</th>
              <th>Project Role / Specialty</th>
              <th>Tasks Assigned</th>
              <th>Task Completion</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($projectRoster as $member)
              @php
                $memberTasks = $tasks->where('assigned_to', $member->user_id);
                $doneCount = $memberTasks->filter(fn($t) => in_array($t->status, ['Done', 'Completed']))->count();
                $totalCount = $memberTasks->count();
                $pctDone = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;
              @endphp
              <tr>
                <td>
                  <div style="display:flex; align-items:center; gap:10px;">
                    <div class="avatar" style="background:var(--primary-soft); color:var(--primary-dark); font-weight:700;">
                      {{ optional($member->user)->initials() }}
                    </div>
                    <div>
                      <div style="font-weight:600; color:var(--ink);">{{ $member->full_name }}</div>
                      <div class="cell-sub">{{ $member->email }}</div>
                    </div>
                  </div>
                </td>
                <td><span style="font-size:12.5px; color:var(--ink-soft);">{{ $member->team_name }}</span></td>
                <td>
                  <span class="badge {{ $member->badge_class }}" style="font-size:11px; font-weight:600;">
                    {{ $member->specialty ?: $member->project_role }}
                  </span>
                </td>
                <td>
                  <div class="mono" style="font-weight:600; font-size:12.5px;">
                    {{ $doneCount }} / {{ $totalCount }} done
                  </div>
                </td>
                <td style="min-width:160px;">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <div class="progressbar" style="flex:1;">
                      <div style="width:{{ $pctDone }}%"></div>
                    </div>
                    <span class="mono" style="font-size:11px; color:var(--ink-soft);">{{ $pctDone }}%</span>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align:center; padding:24px; color:var(--ink-faint);">
                  No roster participants configured yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()))
        <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--line);">
          <form method="POST" action="{{ route('projects.members.add', $project) }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            @csrf
            <input
              type="text"
              name="user_id"
              list="project-available-users-datalist"
              placeholder="Type any member name (e.g. Alex Morgan) or select..."
              required
              autocomplete="off"
              style="flex:1; min-width:200px; border:1px solid var(--line); border-radius:6px; padding:7px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);"
            >
            <datalist id="project-available-users-datalist">
              @foreach ($assignableUsers as $au)
                <option value="{{ $au['raw_name'] }}">{{ $au['name'] }}</option>
              @endforeach
            </datalist>

            <select name="specialty" style="border:1px solid var(--line); border-radius:6px; padding:7px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);">
              <option value="">— Select Specialty —</option>
              <option value="UI/UX Designer">UI/UX Designer</option>
              <option value="Frontend Developer">Frontend Developer</option>
              <option value="Backend Developer">Backend Developer</option>
              <option value="Full Stack Developer">Full Stack Developer</option>
              <option value="QA / Test Engineer">QA / Test Engineer</option>
              <option value="DevOps Engineer">DevOps Engineer</option>
              <option value="System Analyst">System Analyst</option>
              <option value="Database Administrator">Database Administrator</option>
              <option value="Technical Advisor">Technical Advisor</option>
            </select>

            <button type="submit" class="btn btn-primary" style="padding:7px 14px; font-size:12.5px;">+ Add to Roster</button>
          </form>
        </div>
      @endif
    </div>
  </div>

  <!-- PHASES TAB -->
  <div x-show="tab === 'phases'" x-cloak>
    <div class="card card-pad" style="margin-bottom:18px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
          <h3 style="margin:0; font-size:16px; font-weight:700;">Project Lifecycle Phases</h3>
          <p style="font-size:12.5px; color:var(--ink-soft); margin:2px 0 0;">Track milestone progress through standard delivery stages.</p>
        </div>
      </div>
      @include('partials.phase-rail', ['currentIndex' => $project->currentPhaseIndex(), 'mini' => false, 'project' => $project])

      @if ($project->isManagedBy(auth()->user()) || auth()->user()->can('edit_projects'))
        <div style="margin-top:20px; padding-top:16px; border-top:1px solid var(--line);">
          <h4 style="font-size:13.5px; font-weight:700; margin-bottom:12px;">Update Phase Status</h4>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-bottom:16px;">
            @foreach ($project->phases as $ph)
              <div style="background:var(--bg-subtle); border:1px solid var(--line); border-radius:8px; padding:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                  <strong style="font-size:13px;">{{ $ph->phase_name }}</strong>
                  <span class="mono" style="font-size:11px; color:var(--ink-soft);">{{ $ph->tasks->count() }} task(s)</span>
                </div>
                <form method="POST" action="{{ route('phases.status', $ph) }}" style="display:flex; gap:6px; align-items:center;">
                  @csrf
                  <select name="status" style="flex:1; border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12px; font-family:inherit; background:var(--surface);">
                    <option value="Not started" {{ $ph->status === 'Not started' ? 'selected' : '' }}>Not started</option>
                    <option value="In Progress" {{ $ph->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Done" {{ in_array($ph->status, ['Done', 'Completed', 'Closed']) ? 'selected' : '' }}>Done</option>
                  </select>
                  <button type="submit" class="btn btn-ghost" style="padding:4px 8px; font-size:11px;">Update</button>
                </form>
              </div>
            @endforeach
          </div>

          <!-- Add Phase Form -->
          <div style="background:var(--bg-subtle); border:1px dashed var(--line); border-radius:8px; padding:12px;">
            <h5 style="font-size:12.5px; margin-bottom:8px; font-weight:700;">+ Add New Phase</h5>
            <form method="POST" action="{{ route('phases.store', $project) }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
              @csrf
              <input type="text" name="phase_name" required placeholder="Phase name (e.g. Deployment, UAT)" style="flex:1; min-width:180px; border:1px solid var(--line); border-radius:6px; padding:5px 10px; font-size:12px; font-family:inherit; background:var(--surface);">
              <select name="status" style="border:1px solid var(--line); border-radius:6px; padding:5px 10px; font-size:12px; font-family:inherit; background:var(--surface);">
                <option value="Not started" selected>Not started</option>
                <option value="In Progress">In Progress</option>
                <option value="Done">Done</option>
              </select>
              <button type="submit" class="btn btn-accent" style="padding:5px 12px; font-size:12px;">Add Phase</button>
            </form>
          </div>
        </div>
      @endif
    </div>
  </div>

  <!-- DELIVERABLES TAB -->
  <div x-show="tab === 'deliverables'" x-cloak>
    <div class="card card-pad" style="margin-bottom:18px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
          <h3 style="margin:0; font-size:16px; font-weight:700;">Project Scope Deliverables ({{ $project->deliverables->count() }})</h3>
          <p style="font-size:12.5px; color:var(--ink-soft); margin:2px 0 0;">Key project milestones, artifacts, and release packages.</p>
        </div>
      </div>

      <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th style="width:38%;">Deliverable</th>
              <th>Description</th>
              <th>Due Date</th>
              <th>Status</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($project->deliverables as $d)
              <tr>
                <td class="cell-primary">{{ $d->deliverable_name }}</td>
                <td><span style="font-size:12px; color:var(--ink-soft);">{{ $d->description ?: '—' }}</span></td>
                <td><span class="cell-sub">{{ optional($d->due_date)->format('d M Y') ?: 'Not set' }}</span></td>
                <td>
                  <x-status-badge :status="$d->status" />
                </td>
                <td style="text-align:right;">
                  @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()))
                    <form method="POST" action="{{ route('projects.deliverables.toggle', [$project, $d]) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn btn-ghost" style="padding:3px 8px; font-size:11.5px;">
                        {{ $d->status === 'Delivered' ? 'Mark Pending' : '✓ Mark Delivered' }}
                      </button>
                    </form>
                    <form method="POST" action="{{ route('projects.deliverables.destroy', [$project, $d]) }}" onsubmit="return confirm('Remove deliverable {{ $d->deliverable_name }}?');" style="display:inline;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-ghost" style="padding:3px 7px; font-size:11.5px; color:var(--danger);">✕</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align:center; padding:24px; color:var(--ink-faint);">
                  No scope deliverables defined yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if (auth()->user()->can('edit_projects') && $project->isManagedBy(auth()->user()))
        <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--line);">
          <h5 style="margin:0 0 8px; font-size:13px; font-weight:700;">+ Add Scope Deliverable</h5>
          <form method="POST" action="{{ route('projects.deliverables.store', $project) }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            @csrf
            <input type="text" name="deliverable_name" required placeholder="Deliverable name (e.g. SRS Document, Production Deployment)" style="flex:1; min-width:200px; border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);">
            <input type="text" name="description" placeholder="Description (optional)" style="flex:1; min-width:180px; border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);">
            <input type="date" name="due_date" style="border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);">
            <button type="submit" class="btn btn-accent" style="padding:6px 14px; font-size:12.5px;">Add Deliverable</button>
          </form>
        </div>
      @endif
    </div>
  </div>

  <div x-show="tab === 'budget'" x-cloak>
    @php
      $b = $project->budget;
      $util = $b ? $b->utilisationPercent() : 0;
      $totalAlloc = $b ? (float)$b->allocated_amount : 0;
      $totalSp = $b ? (float)$b->spent_amount : 0;
      $remaining = max(0, $totalAlloc - $totalSp);
    @endphp
    <div class="grid grid-4" style="margin-bottom:18px;">
      <div class="card stat-card">
        <div class="stat-label">Total Allocated</div>
        <div class="stat-value" style="font-size:19px;">ETB {{ number_format($totalAlloc) }}</div>
        <div class="stat-delta">Project budget</div>
      </div>
      <div class="card stat-card">
        <div class="stat-label">Total Spent</div>
        <div class="stat-value" style="font-size:19px;">ETB {{ number_format($totalSp) }}</div>
        <div class="stat-delta">Actual expenditure</div>
      </div>
      <div class="card stat-card">
        <div class="stat-label">Remaining Funds</div>
        <div class="stat-value" style="font-size:19px; color:var(--success);">ETB {{ number_format($remaining) }}</div>
        <div class="stat-delta">Available balance</div>
      </div>
      <div class="card stat-card">
        <div class="stat-label">Utilisation</div>
        <div class="stat-value" style="font-size:19px;">{{ $util }}%</div>
        <div class="progressbar {{ $util > 85 ? 'danger' : ($util > 65 ? 'warn' : '') }}" style="margin-top:6px;">
          <div style="width:{{ $util }}%"></div>
        </div>
      </div>
    </div>

    <div class="two-col">
      <div class="card card-pad">
        <div class="card-title-row">
          <h3>Budget by Phase</h3>
          @if (auth()->user()->can('manage_budgets') || $project->isManagedBy(auth()->user()))
            <span class="cell-sub">Click any phase to update spend</span>
          @endif
        </div>
        @foreach ($project->phases as $ph)
          @php
            $pb = $ph->budget;
            $alloc = $pb ? (float)$pb->allocated_amount : 0;
            $sp = $pb ? (float)$pb->spent_amount : 0;
            $pct = $alloc > 0 ? round(($sp / $alloc) * 100) : 0;
          @endphp
          <div style="margin-bottom:16px; background:var(--surface-alt); border:1px solid var(--line); border-radius:8px; padding:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <strong style="font-size:13.5px;">{{ $ph->phase_name }}</strong>
              <div style="display:flex; align-items:center; gap:8px;">
                <span class="mono" style="font-size:12px; color:var(--ink-soft);">
                  ETB {{ number_format($sp) }} / {{ number_format($alloc) }} ({{ $pct }}%)
                </span>
                @if (auth()->user()->can('manage_budgets') || $project->isManagedBy(auth()->user()))
                  <button
                    type="button"
                    class="btn btn-ghost"
                    style="padding:2px 7px; font-size:11px;"
                    @click="selectedPhaseId = {{ $ph->phase_id }}; phaseAllocated = {{ $alloc }}; phaseSpent = {{ $sp }}; editBudgetModal = true;"
                  >
                    ⚙ Edit
                  </button>
                @endif
              </div>
            </div>
            <div class="progressbar {{ $pct > 85 ? 'danger' : ($pct > 65 ? 'warn' : '') }}">
              <div style="width:{{ $pct }}%"></div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="card card-pad">
        <div class="card-title-row">
          <h3>Overall Project Budget</h3>
        </div>
        @if (auth()->user()->can('manage_budgets') || $project->isManagedBy(auth()->user()))
          <form method="POST" action="{{ route('budgets.projects.update', $project) }}">
            @csrf
            <div class="form-field">
              <label>Total Allocated Budget (ETB)</label>
              <input type="number" step="0.01" min="0" name="allocated_amount" value="{{ $totalAlloc }}" required>
            </div>
            <div class="form-field">
              <label>Total Spent Budget (ETB)</label>
              <input type="number" step="0.01" min="0" name="spent_amount" value="{{ $totalSp }}" required>
            </div>
            <button type="submit" class="btn btn-accent" style="margin-top:4px;">Save Overall Budget</button>
          </form>
        @else
          <div style="font-size:13px; color:var(--ink-soft); line-height:1.6;">
            <p><strong>Total Allocated:</strong> ETB {{ number_format($totalAlloc) }}</p>
            <p><strong>Total Spent:</strong> ETB {{ number_format($totalSp) }}</p>
            <p><strong>Remaining:</strong> ETB {{ number_format($remaining) }}</p>
          </div>
        @endif
      </div>
    </div>

    {{-- Phase Budget Modal --}}
    <template x-if="editBudgetModal && selectedPhaseId">
      <div>
        <div class="overlay show" @click="editBudgetModal = false"></div>
        <div class="card card-pad" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1000; width:420px; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="margin:0; font-size:15px;">Update Phase Budget</h3>
            <button type="button" @click="editBudgetModal = false" style="background:none; border:none; font-size:18px; cursor:pointer; color:var(--ink-faint);">&times;</button>
          </div>
          <form :action="'/budgets/phases/' + selectedPhaseId" method="POST">
            @csrf
            <div class="form-field">
              <label>Allocated Amount (ETB)</label>
              <input type="number" step="0.01" min="0" name="allocated_amount" x-model="phaseAllocated" required>
            </div>
            <div class="form-field">
              <label>Spent Amount (ETB)</label>
              <input type="number" step="0.01" min="0" name="spent_amount" x-model="phaseSpent" required>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
              <button type="button" class="btn btn-ghost" @click="editBudgetModal = false">Cancel</button>
              <button type="submit" class="btn btn-accent">Save Phase Budget</button>
            </div>
          </form>
        </div>
      </div>
    </template>
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

<script>
  let draggedProjTaskId = null;

  function handleProjDragStart(e, taskId) {
    draggedProjTaskId = taskId;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', taskId);
    e.target.style.opacity = '0.5';
  }

  function handleProjDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }

  async function handleProjDrop(e, targetStatus) {
    e.preventDefault();
    if (!draggedProjTaskId) return;

    const card = document.getElementById('proj-task-card-' + draggedProjTaskId);
    if (card) {
      card.style.opacity = '1';
      const targetColBody = document.getElementById('proj-tasks-' + slugify(targetStatus));
      if (targetColBody) {
        targetColBody.appendChild(card);
      }
    }

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch(`/tasks/${draggedProjTaskId}/status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ status: targetStatus })
      });

      if (res.ok) {
        updateProjKanbanCounts();
      }
    } catch(err) {
      console.error('Failed to update status:', err);
    } finally {
      draggedProjTaskId = null;
    }
  }

  function slugify(text) {
    return text.toString().toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^\w\-]+/g, '')
      .replace(/\-\-+/g, '-')
      .replace(/^-+/, '')
      .replace(/-+$/, '');
  }

  function updateProjKanbanCounts() {
    ['To Do', 'In Progress', 'In Review', 'Completed', 'Blocked'].forEach(st => {
      const col = document.getElementById('proj-tasks-' + slugify(st));
      const countEl = document.getElementById('proj-count-' + slugify(st));
      if (col && countEl) {
        const count = col.querySelectorAll('.tcard').length;
        countEl.innerText = count;
      }
    });
  }

  document.addEventListener('dragend', (e) => {
    if (e.target.classList.contains('tcard')) {
      e.target.style.opacity = '1';
    }
  });
</script>
