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
                            <option value="{{ $t->name }}" data-office="{{ $t->office_id ?? '' }}" {{ old('project_type', $project->project_type) === $t->name ? 'selected' : '' }}>
                                {{ $t->name }}@if ($t->office_id) · {{ optional($t->office)->office_name }}@endif</option>
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

            <div class="form-field">
                <label for="primary_office_id">Primary Office</label>
                <select id="primary_office_id" name="primary_office_id">
                    <option value="">— No primary office —</option>
                    @foreach ($offices as $office)
                        <option value="{{ $office->office_id }}"
                            {{ (string) old('primary_office_id', $project->primary_office_id) === (string) $office->office_id ? 'selected' : '' }}>
                            {{ $office->office_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label>Participating Offices
                    <span style="font-weight:400; color:var(--ink-faint);">(cross-office collaboration)</span>
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:6px;">
                    @foreach ($offices as $office)
                        <label style="display:flex; align-items:center; gap:6px; font-weight:400; font-size:13px;">
                            <input type="checkbox" name="participating_offices[]" value="{{ $office->office_id }}"
                                {{ $project->offices->contains('office_id', $office->office_id) && (int) $project->primary_office_id !== (int) $office->office_id ? 'checked' : '' }}>
                            {{ $office->office_name }}
                        </label>
                    @endforeach
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
                <div style="margin-top:-38px; display:flex; justify-content:flex-end;"
                    x-data="{ showDeleteModal: false }">
                    <form id="deleteProjectForm" method="POST" action="{{ route('projects.destroy', $project) }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-ghost"
                            style="color:var(--danger); border-color:var(--danger-soft);"
                            @click="showDeleteModal = true">Delete project</button>
                    </form>

                    {{-- Delete confirmation modal --}}
                    <template x-if="showDeleteModal">
                        <div>
                            <div class="overlay show" @click="showDeleteModal = false"></div>
                            <div class="card card-pad"
                                style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1000; width:420px; box-shadow:0 15px 35px rgba(0,0,0,0.2);"
                                role="dialog" aria-modal="true">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                                    <h3 style="margin:0; font-size:16px; font-weight:700;">Delete
                                        '{{ $project->project_name }}'?</h3>
                                    <button type="button" @click="showDeleteModal = false"
                                        style="background:none; border:none; font-size:18px; cursor:pointer; color:var(--ink-faint);">&times;</button>
                                </div>
                                <p style="margin:0 0 18px; font-size:14px; line-height:1.55; color:var(--ink-soft);">
                                    This removes all its phases, tasks, and budget data. This action cannot be undone.
                                </p>
                                <div style="display:flex; justify-content:flex-end; gap:8px;">
                                    <button type="button" class="btn btn-ghost"
                                        @click="showDeleteModal = false">Cancel</button>
                                    <button type="button" class="btn"
                                        style="background:var(--danger); border-color:var(--danger); color:#fff;"
                                        @click="document.getElementById('deleteProjectForm').submit()">Yes, delete
                                        it</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
        @endcan
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const officeSelect = document.getElementById('primary_office_id');
        const typeSelect = document.getElementById('project_type');
        if (!officeSelect || !typeSelect) return;

        const allOptions = Array.from(typeSelect.options);
        const currentOffice = officeSelect.value;

        function filterTypes(officeId) {
            const selected = typeSelect.value;
            typeSelect.innerHTML = '';
            const visible = allOptions.filter(o => !o.dataset.office || o.dataset.office === '' || o.dataset.office === officeId);
            visible.forEach(o => typeSelect.appendChild(o));
            if (visible.some(o => o.value === selected)) {
                typeSelect.value = selected;
            } else {
                typeSelect.value = '';
            }
        }

        filterTypes(currentOffice);
        officeSelect.addEventListener('change', () => filterTypes(officeSelect.value));
    })();
</script>
@endpush
