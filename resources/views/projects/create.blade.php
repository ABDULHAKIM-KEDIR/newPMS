@extends('layouts.app')
@section('title', 'New Project')
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('projects.index') }}">Projects</a> <b>/ New Project Wizard</b>
@endsection

@section('content')
<div class="page-head">
  <div>
    <h1>Create New Project</h1>
    <div class="page-sub">Set up project information, assign teams, configure initial tasks, and review before launch.</div>
  </div>
</div>

@if ($errors->any())
  <div class="form-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<!-- Step Indicator -->
<div class="card" style="margin-bottom:24px; padding:18px 24px;">
  <div class="wizard-stepper" style="display:flex; justify-content:space-between; position:relative; align-items:center;">
    <div class="wizard-step active" id="step-nav-1" onclick="goToStep(1)">
      <div class="wizard-step-circle">1</div>
      <div class="wizard-step-info">
        <span class="step-num">Step 1</span>
        <span class="step-title">Project Info</span>
      </div>
    </div>
    <div class="wizard-line" id="line-1"></div>
    <div class="wizard-step" id="step-nav-2" onclick="goToStep(2)">
      <div class="wizard-step-circle">2</div>
      <div class="wizard-step-info">
        <span class="step-num">Step 2</span>
        <span class="step-title">Assign Teams</span>
      </div>
    </div>
    <div class="wizard-line" id="line-2"></div>
    <div class="wizard-step" id="step-nav-3" onclick="goToStep(3)">
      <div class="wizard-step-circle">3</div>
      <div class="wizard-step-info">
        <span class="step-num">Step 3</span>
        <span class="step-title">Create Tasks</span>
      </div>
    </div>
    <div class="wizard-line" id="line-3"></div>
    <div class="wizard-step" id="step-nav-4" onclick="goToStep(4)">
      <div class="wizard-step-circle">4</div>
      <div class="wizard-step-info">
        <span class="step-num">Step 4</span>
        <span class="step-title">Review & Confirm</span>
      </div>
    </div>
  </div>
</div>

<form id="project-wizard-form" method="POST" action="{{ route('projects.store') }}">
  @csrf

  <!-- STEP 1: Project Information -->
  <div class="card card-pad wizard-pane active" id="pane-1">
    <div style="border-bottom:1px solid var(--line); padding-bottom:14px; margin-bottom:20px;">
      <h2 style="font-size:17px; font-weight:700; margin:0;">Step 1 — Project Information</h2>
      <p style="font-size:13px; color:var(--ink-soft); margin:4px 0 0;">Enter primary project details, client, timeline, and leadership.</p>
    </div>

    <div class="form-grid">
      <div class="form-field" style="grid-column:1 / -1;">
        <label for="project_name">Project Name <span style="color:var(--danger);">*</span></label>
        <input type="text" id="project_name" name="project_name" value="{{ old('project_name', 'E-Commerce Website') }}" required placeholder="e.g. E-Commerce Website">
      </div>

      <div class="form-field" style="grid-column:1 / -1;">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" placeholder="Project goals, requirements, scope...">{{ old('description', 'A modern, responsive e-commerce web platform with integrated product catalog, cart, and payment processing.') }}</textarea>
      </div>

      <div class="form-field">
        <label for="client">Client / Organization</label>
        <input type="text" id="client" name="client" value="{{ old('client', 'Global Retail Corporation') }}" placeholder="e.g. Retail Corp, Ministry of Health">
      </div>

      <div class="form-field">
        <label for="project_type">Project Type <span style="color:var(--danger);">*</span></label>
        <select id="project_type" name="project_type" required>
          @foreach ($types as $t)
            <option value="{{ $t }}" {{ old('project_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-field">
        <label for="project_manager_id">
          Project Manager <span style="color:var(--danger);">*</span>
        </label>
        <select id="project_manager_id" name="project_manager_id" required>
          <option value="">— Select Project Manager —</option>
          @foreach ($projectManagers as $pm)
            <option value="{{ $pm->user_id }}" {{ (string) old('project_manager_id') === (string) $pm->user_id || $pm->full_name === 'John Smith' ? 'selected' : '' }}>
              {{ $pm->full_name }} ({{ $pm->department ?: 'PM' }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-field">
        <label for="priority">Priority <span style="color:var(--danger);">*</span></label>
        <select id="priority" name="priority" required>
          @foreach ($priorities as $p)
            <option value="{{ $p }}" {{ (old('priority', 'High') === $p) ? 'selected' : '' }}>{{ $p }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-field">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}">
      </div>

      <div class="form-field">
        <label for="end_date">Deadline (Target End Date)</label>
        <input type="date" id="end_date" name="end_date" value="{{ old('end_date', now()->addMonths(3)->toDateString()) }}">
      </div>

      <div class="form-field">
        <label for="allocated_amount">Allocated Budget (ETB)</label>
        <input type="number" step="0.01" min="0" id="allocated_amount" name="allocated_amount" value="{{ old('allocated_amount', 750000) }}" placeholder="e.g. 750000">
      </div>
    </div>

    <div class="wizard-actions">
      <div></div>
      <button type="button" class="btn btn-accent" onclick="validateStep1AndNext()">Next: Assign Teams →</button>
    </div>
  </div>



  <!-- STEP 2: Assign Teams -->
  <div class="card card-pad wizard-pane" id="pane-2">
    <div style="border-bottom:1px solid var(--line); padding-bottom:14px; margin-bottom:20px;">
      <h2 style="font-size:17px; font-weight:700; margin:0;">Step 2 — Assign Teams</h2>
      <p style="font-size:13px; color:var(--ink-soft); margin:4px 0 0;">Select one or more specialized teams responsible for delivering this project.</p>
    </div>

    <div class="teams-selection-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; margin-bottom:20px;">
      @foreach ($teams as $team)
        <label class="team-select-card" id="team-card-{{ $team->team_id }}" style="display:flex; align-items:flex-start; gap:14px; padding:16px; border:1.5px solid var(--line); border-radius:10px; cursor:pointer; transition:all 0.15s ease; background:var(--bg-card);">
          <input
            type="checkbox"
            name="teams[]"
            value="{{ $team->team_id }}"
            id="team-checkbox-{{ $team->team_id }}"
            class="team-checkbox"
            style="margin-top:4px; width:18px; height:18px; accent-color:var(--accent); cursor:pointer;"
            onchange="toggleTeamSelection({{ $team->team_id }})"
            {{ in_array($team->team_name, ['UI/UX Team', 'Frontend Team', 'Backend Team', 'Software Engineering']) ? 'checked' : '' }}
          >
          <div style="flex:1;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
              <span style="font-weight:700; font-size:14.5px; color:var(--ink);">{{ $team->team_name }}</span>
              <span class="badge b-active" style="font-size:10px;">{{ $team->members->count() }} members</span>
            </div>
            <div style="font-size:12px; color:var(--ink-soft); margin:4px 0 6px;">
              <b>Lead:</b> {{ optional($team->leader)->full_name ?? 'Unassigned' }}
            </div>
            <div style="font-size:12px; color:var(--ink-muted); line-height:1.4;">
              {{ Str::limit($team->description, 75) }}
            </div>
          </div>
        </label>
      @endforeach
    </div>

    <div class="wizard-actions">
      <button type="button" class="btn btn-ghost" onclick="goToStep(1)">← Back to Info</button>
      <button type="button" class="btn btn-accent" onclick="validateStep2AndNext()">Next: Create Tasks →</button>
    </div>
  </div>

  <!-- STEP 3: Create Tasks -->
  <div class="card card-pad wizard-pane" id="pane-3">
    <div style="border-bottom:1px solid var(--line); padding-bottom:14px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h2 style="font-size:17px; font-weight:700; margin:0;">Step 3 — Create Tasks by Team</h2>
        <p style="font-size:13px; color:var(--ink-soft); margin:4px 0 0;">Define initial tasks for each assigned team and assign them to individual members.</p>
      </div>
    </div>

    <!-- Dynamic container for task creation sections per selected team -->
    <div id="team-task-builders-container">
      <!-- Populated dynamically by JavaScript based on Step 2 selection -->
    </div>

    <div class="wizard-actions">
      <button type="button" class="btn btn-ghost" onclick="goToStep(2)">← Back to Teams</button>
      <button type="button" class="btn btn-accent" onclick="buildReviewAndNext()">Next: Review & Confirm →</button>
    </div>
  </div>

  <!-- STEP 4: Review & Confirm -->
  <div class="card card-pad wizard-pane" id="pane-4">
    <div style="border-bottom:1px solid var(--line); padding-bottom:14px; margin-bottom:20px;">
      <h2 style="font-size:17px; font-weight:700; margin:0;">Step 4 — Review & Launch Project</h2>
      <p style="font-size:13px; color:var(--ink-soft); margin:4px 0 0;">Verify project scope, assigned teams, and configured tasks before launching.</p>
    </div>

    <!-- Review Metrics Grid -->
    <div class="review-stats-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px; margin-bottom:24px;">
      <div class="stat-box" style="background:var(--bg-subtle); padding:14px; border-radius:8px; border:1px solid var(--line);">
        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); margin-bottom:4px;">Project Name</div>
        <div id="review-proj-name" style="font-size:15px; font-weight:700; color:var(--ink);">-</div>
      </div>
      <div class="stat-box" style="background:var(--bg-subtle); padding:14px; border-radius:8px; border:1px solid var(--line);">
        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); margin-bottom:4px;">Project Manager</div>
        <div id="review-pm-name" style="font-size:14px; font-weight:700; color:var(--ink);">-</div>
      </div>
      <div class="stat-box" style="background:var(--bg-subtle); padding:14px; border-radius:8px; border:1px solid var(--line);">
        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); margin-bottom:4px;">Assigned Teams</div>
        <div id="review-teams-count" style="font-size:18px; font-weight:800; color:var(--accent);">0</div>
      </div>
      <div class="stat-box" style="background:var(--bg-subtle); padding:14px; border-radius:8px; border:1px solid var(--line);">
        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); margin-bottom:4px;">Total Tasks</div>
        <div id="review-tasks-count" style="font-size:18px; font-weight:800; color:var(--active);">0</div>
      </div>
      <div class="stat-box" style="background:var(--bg-subtle); padding:14px; border-radius:8px; border:1px solid var(--line);">
        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); margin-bottom:4px;">Priority</div>
        <div id="review-priority-badge">-</div>
      </div>
      <div class="stat-box" style="background:var(--bg-subtle); padding:14px; border-radius:8px; border:1px solid var(--line);">
        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); margin-bottom:4px;">Deadline</div>
        <div id="review-deadline" style="font-size:13.5px; font-weight:600; color:var(--ink);">-</div>
      </div>
    </div>

    <!-- Structured Breakdown of Teams & Tasks -->
    <div style="margin-bottom:24px;">
      <h3 style="font-size:14px; font-weight:700; text-transform:uppercase; color:var(--ink-soft); margin-bottom:12px;">Team & Task Breakdown</h3>
      <div id="review-teams-tasks-list" style="display:flex; flex-direction:column; gap:16px;">
        <!-- Populated via JS -->
      </div>
    </div>

    <div class="wizard-actions">
      <button type="button" class="btn btn-ghost" onclick="goToStep(3)">← Back to Tasks</button>
      <button type="submit" class="btn btn-accent" style="font-size:14px; padding:9px 24px; font-weight:700;">Create Project & Launch Tasks</button>
    </div>
  </div>
</form>

<style>
  .wizard-pane { display: none; }
  .wizard-pane.active { display: block; }
  .wizard-step { display:flex; align-items:center; gap:10px; cursor:pointer; opacity:0.6; transition:all 0.2s ease; }
  .wizard-step.active { opacity:1; }
  .wizard-step.completed .wizard-step-circle { background:var(--active); color:#fff; }
  .wizard-step.active .wizard-step-circle { background:var(--accent); color:#fff; box-shadow:0 0 0 4px rgba(59,130,246,0.18); }
  .wizard-step-circle { width:32px; height:32px; border-radius:50%; background:var(--bg-subtle); border:1.5px solid var(--line); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:var(--ink-soft); }
  .wizard-step-info { display:flex; flex-direction:column; }
  .step-num { font-size:11px; text-transform:uppercase; font-weight:700; color:var(--ink-muted); }
  .step-title { font-size:13.5px; font-weight:700; color:var(--ink); }
  .wizard-line { flex:1; height:2px; background:var(--line); margin:0 14px; }
  .wizard-actions { display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:16px; border-top:1px solid var(--line); }
  .team-task-card { background:var(--bg-subtle); border:1px solid var(--line); border-radius:8px; padding:16px; margin-bottom:16px; }
  .task-row-item { display:grid; grid-template-columns:2fr 1.3fr 1fr 1fr 1fr auto; gap:8px; align-items:center; background:var(--bg-card); border:1px solid var(--line); border-radius:6px; padding:10px 12px; margin-bottom:8px; }
  @media (max-width: 768px) {
    .wizard-stepper { flex-direction:column; gap:12px; align-items:flex-start; }
    .wizard-line { display:none; }
    .task-row-item { grid-template-columns:1fr; }
  }
</style>

<script>
  window.__TEAMS_DATA__ = {!! json_encode($teamsData) !!};

  let currentStep = 1;
  let taskCounter = 0;

  function goToStep(step) {
    if (step === 2 && !validateStep1()) return;
    if (step === 3 && (!validateStep1() || !validateStep2())) return;
    if (step === 4 && (!validateStep1() || !validateStep2())) return;

    currentStep = step;
    document.querySelectorAll('.wizard-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('pane-' + step).classList.add('active');

    for (let i = 1; i <= 4; i++) {
      const el = document.getElementById('step-nav-' + i);
      el.classList.remove('active', 'completed');
      if (i === step) el.classList.add('active');
      else if (i < step) el.classList.add('completed');
    }

    if (step === 3) renderTaskBuilders();
    if (step === 4) buildReviewSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function validateStep1() {
    const name = document.getElementById('project_name').value.trim();
    if (!name) { alert('Please provide a Project Name.'); return false; }
    return true;
  }

  function validateStep1AndNext() {
    if (validateStep1()) goToStep(2);
  }

  function validateStep2() {
    const checked = document.querySelectorAll('.team-checkbox:checked');
    if (checked.length === 0) {
      alert('Please select at least one team for this project.');
      return false;
    }
    return true;
  }

  function validateStep2AndNext() {
    if (validateStep2()) goToStep(3);
  }

  function toggleTeamSelection(teamId) {
    const cb = document.getElementById('team-checkbox-' + teamId);
    const card = document.getElementById('team-card-' + teamId);
    if (cb && card) {
      card.style.borderColor = cb.checked ? 'var(--accent)' : 'var(--line)';
      card.style.background = cb.checked ? 'rgba(59,130,246,0.04)' : 'var(--bg-card)';
    }
  }

  function renderTaskBuilders() {
    const container = document.getElementById('team-task-builders-container');
    const checkedBoxes = Array.from(document.querySelectorAll('.team-checkbox:checked'));
    const selectedTeamIds = checkedBoxes.map(cb => parseInt(cb.value));

    // Preserve existing task rows if any
    const existingValues = [];
    document.querySelectorAll('.task-row-item').forEach(row => {
      const tName = row.querySelector('[name*="[task_name]"]')?.value;
      const tTeam = row.querySelector('[name*="[team_id]"]')?.value;
      const tAssigned = row.querySelector('[name*="[assigned_to]"]')?.value;
      const tPriority = row.querySelector('[name*="[priority]"]')?.value;
      const tDue = row.querySelector('[name*="[end_date]"]')?.value;
      if (tName) {
        existingValues.push({ task_name: tName, team_id: parseInt(tTeam), assigned_to: tAssigned, priority: tPriority, end_date: tDue });
      }
    });

    container.innerHTML = '';

    selectedTeamIds.forEach(teamId => {
      const team = window.__TEAMS_DATA__.find(t => t.id === teamId);
      if (!team) return;

      const card = document.createElement('div');
      card.className = 'team-task-card';
      card.id = 'team-task-block-' + teamId;

      card.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <div>
            <span style="font-weight:700; font-size:15px; color:var(--ink);">${team.name}</span>
            <span style="font-size:12px; color:var(--ink-soft); margin-left:8px;">(Lead: ${team.leader_name})</span>
          </div>
          <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px;" onclick="addTaskRow(${team.id})">+ Add Task</button>
        </div>
        <div id="task-rows-team-${team.id}"></div>
      `;

      container.appendChild(card);

      const teamExisting = existingValues.filter(v => v.team_id === teamId);
      if (teamExisting.length > 0) {
        teamExisting.forEach(v => addTaskRow(teamId, v));
      } else {
        // Provide sample starter tasks based on team
        if (team.name.includes('UI/UX')) {
          addTaskRow(teamId, { task_name: 'Create Wireframes', priority: 'High', status: 'To Do' });
          addTaskRow(teamId, { task_name: 'Design Product Page', priority: 'High', status: 'To Do' });
        } else if (team.name.includes('Frontend')) {
          addTaskRow(teamId, { task_name: 'Build Homepage Interface', priority: 'High', status: 'To Do' });
          addTaskRow(teamId, { task_name: 'Build Product Listing & Cart', priority: 'Medium', status: 'To Do' });
        } else if (team.name.includes('Backend')) {
          addTaskRow(teamId, { task_name: 'Create Authentication API', priority: 'High', status: 'To Do' });
          addTaskRow(teamId, { task_name: 'Create Product Catalog API', priority: 'High', status: 'To Do' });
        } else {
          addTaskRow(teamId);
        }
      }
    });
  }

  function addTaskRow(teamId, prefill = {}) {
    const rowsContainer = document.getElementById('task-rows-team-' + teamId);
    if (!rowsContainer) return;

    const team = window.__TEAMS_DATA__.find(t => t.id === teamId);
    taskCounter++;
    const idx = taskCounter;

    let memberOptions = ``;
    if (team && team.members) {
      team.members.forEach(m => {
        memberOptions += `<option value="${m.name}">${m.name}</option>`;
      });
    }

    const row = document.createElement('div');
    row.className = 'task-row-item';
    row.id = 'task-row-' + idx;

    row.innerHTML = `
      <input type="hidden" name="tasks[${idx}][team_id]" value="${teamId}">
      <div>
        <input type="text" name="tasks[${idx}][task_name]" value="${prefill.task_name || ''}" placeholder="Task title (e.g. Build API)" required style="width:100%;">
      </div>
      <div>
        <input type="text" name="tasks[${idx}][assigned_to]" list="task-member-datalist-${idx}" value="${prefill.assigned_to || ''}" placeholder="Assignee (pick or type name)..." style="width:100%;" autocomplete="off">
        <datalist id="task-member-datalist-${idx}">
          ${memberOptions}
        </datalist>
      </div>
      <div>
        <select name="tasks[${idx}][priority]" style="width:100%;">
          <option value="High" ${prefill.priority === 'High' ? 'selected' : ''}>High</option>
          <option value="Medium" ${(!prefill.priority || prefill.priority === 'Medium') ? 'selected' : ''}>Medium</option>
          <option value="Low" ${prefill.priority === 'Low' ? 'selected' : ''}>Low</option>
          <option value="Urgent" ${prefill.priority === 'Urgent' ? 'selected' : ''}>Urgent</option>
        </select>
      </div>
      <div>
        <input type="number" step="0.01" min="0" name="tasks[${idx}][budget]" value="${prefill.budget || ''}" placeholder="Budget (ETB)" style="width:100%;">
      </div>
      <div>
        <input type="date" name="tasks[${idx}][end_date]" value="${prefill.end_date || document.getElementById('end_date').value || ''}" style="width:100%;">
      </div>
      <div>
        <button type="button" class="btn btn-ghost" style="padding:4px 8px; color:var(--danger);" onclick="document.getElementById('task-row-${idx}').remove()">✕</button>
      </div>
    `;

    rowsContainer.appendChild(row);
  }

  function buildReviewAndNext() {
    buildReviewSummary();
    goToStep(4);
  }

  function buildReviewSummary() {
    document.getElementById('review-proj-name').innerText = document.getElementById('project_name').value || 'Untitled';
    const pmSelect = document.getElementById('project_manager_id');
    document.getElementById('review-pm-name').innerText = pmSelect.options[pmSelect.selectedIndex]?.text || 'Unassigned';
    document.getElementById('review-deadline').innerText = document.getElementById('end_date').value || 'Not set';

    const pri = document.getElementById('priority').value;
    document.getElementById('review-priority-badge').innerHTML = `<span class="badge p-${pri.toLowerCase()}">${pri}</span>`;

    const selectedTeams = Array.from(document.querySelectorAll('.team-checkbox:checked'));
    document.getElementById('review-teams-count').innerText = selectedTeams.length;

    const taskRows = document.querySelectorAll('.task-row-item');
    let validTasksCount = 0;
    const teamTasksMap = {};

    selectedTeams.forEach(cb => {
      const tid = parseInt(cb.value);
      const team = window.__TEAMS_DATA__.find(t => t.id === tid);
      if (team) {
        teamTasksMap[tid] = { name: team.name, lead: team.leader_name, tasks: [] };
      }
    });

    taskRows.forEach(row => {
      const name = row.querySelector('[name*="[task_name]"]')?.value.trim();
      const teamId = parseInt(row.querySelector('[name*="[team_id]"]')?.value);
      const assigneeInput = row.querySelector('[name*="[assigned_to]"]')?.value.trim();
      const assigneeName = assigneeInput || 'Unassigned';
      const pri = row.querySelector('[name*="[priority]"]')?.value || 'Medium';
      const due = row.querySelector('[name*="[end_date]"]')?.value;
      const bgt = row.querySelector('[name*="[budget]"]')?.value;

      if (name && teamTasksMap[teamId]) {
        validTasksCount++;
        teamTasksMap[teamId].tasks.push({ name, assignee: assigneeName, priority: pri, due, budget: bgt });
      }
    });

    document.getElementById('review-tasks-count').innerText = validTasksCount;

    const listContainer = document.getElementById('review-teams-tasks-list');
    listContainer.innerHTML = '';

    Object.values(teamTasksMap).forEach(teamInfo => {
      const block = document.createElement('div');
      block.style.background = 'var(--bg-subtle)';
      block.style.border = '1px solid var(--line)';
      block.style.borderRadius = '8px';
      block.style.padding = '14px 16px';

      let taskListHtml = '';
      if (teamInfo.tasks.length === 0) {
        taskListHtml = `<div style="font-size:12.5px; color:var(--ink-muted); font-style:italic;">No initial tasks added for this team.</div>`;
      } else {
        taskListHtml = teamInfo.tasks.map(t => `
          <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:var(--bg-card); border:1px solid var(--line); border-radius:6px; margin-top:6px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="color:var(--accent);">✓</span>
              <span style="font-weight:600; font-size:13.5px; color:var(--ink);">${t.name}</span>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
              <span style="font-size:12px; color:var(--ink-soft);">👤 ${t.assignee}</span>
              <span class="badge p-${t.priority.toLowerCase()}">${t.priority}</span>
              ${t.due ? `<span style="font-size:11.5px; color:var(--ink-muted);">Due ${t.due}</span>` : ''}
            </div>
          </div>
        `).join('');
      }

      block.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span style="font-weight:700; font-size:14.5px; color:var(--ink);">${teamInfo.name}</span>
          <span class="badge b-active">${teamInfo.tasks.length} task(s)</span>
        </div>
        ${taskListHtml}
      `;

      listContainer.appendChild(block);
    });
  }

  // Initial setup for styled cards
  document.querySelectorAll('.team-checkbox').forEach(cb => {
    toggleTeamSelection(cb.value);
  });
</script>
@endsection
