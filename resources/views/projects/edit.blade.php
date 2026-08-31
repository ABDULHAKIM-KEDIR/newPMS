@extends('layouts.app')
@section('title', 'Edit Project')
@section('crumb')
    <a class="link-small" style="cursor:pointer;"
        href="{{ route('projects.show', $project) }}">{{ $project->project_name }}</a> <b>/ Edit</b>
@endsection

@section('content')
    <div class="page-head">
        <div>
            <h1>Edit Project</h1>
            <div class="page-sub">Update project details, Project Manager, and Team assignment</div>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert">
            <ul>@foreach ($errors->all() as $e)
            <li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card card-pad" style="max-width:680px;">
        <!-- MAIN UPDATE FORM -->
        <form method="POST" action="{{ route('projects.update', $project) }}">
            @csrf
            @method('PUT')

            <div class="form-field">
                <label for="project_name">Project name <span style="color:var(--danger);">*</span></label>
                <input type="text" id="project_name" name="project_name"
                    value="{{ old('project_name', $project->project_name) }}" required autofocus>
            </div>

            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{ old('description', $project->description) }}</textarea>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="project_type">Type <span class="required-mark">*</span></label>
                    <select id="project_type" name="project_type" required>
                        @foreach ($projectTypes as $t)
                            <option value="{{ $t->name }}" {{ old('project_type', $project->project_type) === $t->name ? 'selected' : '' }}>
                                {{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label for="status">Status <span style="color:var(--danger);">*</span></label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" {{ old('status', $project->status) === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="project_manager_id">
                        Project Manager
                        <span style="font-weight:400; font-size:11.5px; color:var(--ink-soft);">(Type any name or
                            select)</span>
                    </label>
                    <input type="text" id="project_manager_id" name="project_manager_id" list="pm-suggestions-list"
                        value="{{ old('project_manager_id', optional($project->projectManager)->full_name) }}"
                        placeholder="Type any name (e.g. Abebe Bikila) or pick..." autocomplete="off">
                    <datalist id="pm-suggestions-list">
                        @foreach ($projectManagers as $pm)
                            <option value="{{ $pm->full_name }}">{{ $pm->full_name }} ({{ $pm->email }})</option>
                        @endforeach
                    </datalist>
                </div>

                <div class="form-field">
                    <label for="team_id">
                        Assigned Team <span style="color:var(--danger);">*</span>
                    </label>
                    <select id="team_id" name="team_id" required>
                        @foreach ($teams as $team)
                            <option value="{{ $team->team_id }}" {{ (string) old('team_id', $project->team_id) === (string) $team->team_id ? 'selected' : '' }}>
                                {{ $team->team_name }} (Leader: {{ optional($team->leader)->full_name ?? 'Unassigned' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="start_date">Start date</label>
                    <input type="date" id="start_date" name="start_date"
                        value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}">
                </div>
                <div class="form-field">
                    <label for="end_date">Target end date</label>
                    <input type="date" id="end_date" name="end_date"
                        value="{{ old('end_date', optional($project->end_date)->format('Y-m-d')) }}">
                </div>
            </div>

            @if ($canEditBudget)
                <div class="form-field">
                    <label for="allocated_amount">Budget allocated (ETB)</label>
                    <input type="number" step="0.01" min="0" id="allocated_amount" name="allocated_amount"
                        value="{{ old('allocated_amount', optional($project->budget)->allocated_amount) }}">
                </div>
            @else
                <div class="field-row" style="margin-bottom:16px;">
                    <span class="k">Budget allocated</span>
                    <span class="v">ETB {{ number_format(optional($project->budget)->allocated_amount ?? 0) }} <span
                            style="font-weight:400; color:var(--ink-faint); font-size:11.5px;">(requires manage_budgets
                            permission)</span></span>
                </div>
            @endif

            <div
                style="display:flex; gap:10px; margin-top:24px; padding-top:16px; border-top:1px solid var(--line); justify-content:space-between; align-items:center;">
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-accent" style="padding:8px 20px; font-weight:600;">Save
                        Changes</button>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>

        <!-- SEPARATE DELETE FORM (OUTSIDE MAIN FORM) -->
        @can('delete_projects')
            @if ($project->isManagedBy(auth()->user()))
                <div style="margin-top:-38px; display:flex; justify-content:flex-end;">
                    <form method="POST" action="{{ route('projects.destroy', $project) }}"
                        onsubmit="return confirm('Delete \'{{ $project->project_name }}\' permanently? This removes all its phases, tasks, and budget data.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost"
                            style="color:var(--danger); border-color:var(--danger-soft);">Delete project</button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
@endsection
