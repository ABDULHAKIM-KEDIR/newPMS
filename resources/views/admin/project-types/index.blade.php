@extends('layouts.app')

@section('title', 'Project Types')

@section('crumb', 'Settings · Project Types')

@section('content')

<div class="page-head">
    <div>
        <h1>Project Types</h1>
        <div class="page-sub">
            The catalogue of project types offered in the project creation wizard
        </div>
    </div>
</div>

@if (session('status'))
    <div class="flash-status">{{ session('status') }}</div>
@endif

@error('name')
    <div class="flash-error">{{ $message }}</div>
@enderror

<div class="card" style="margin-bottom:20px; padding:20px;">
    <h2 style="font-family:'Space Grotesk'; font-size:14px; font-weight:600; margin:0 0 14px;">
        @if ($editing)
            Edit “{{ $editing->name }}”
        @else
            + New Project Type
        @endif
    </h2>

    <form
        method="POST"
        @if ($editing)
            action="{{ route('admin.project-types.update', $editing) }}"
        @else
            action="{{ route('admin.project-types.store') }}"
        @endif
        style="display:grid; grid-template-columns:1fr 2fr 1fr auto; gap:12px; align-items:end;"
    >
        @if (request('office_id') && request('office_id') !== 'all')
            <input type="hidden" name="office_id" value="{{ request('office_id') }}">
        @endif
        @csrf
        @if ($editing) @method('PUT') @endif

        <div>
            <label for="name" style="display:block; font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:6px;">
                Name <span style="color:var(--danger);">*</span>
            </label>
            <input
                type="text" id="name" name="name" required maxlength="100"
                value="{{ old('name', $editing?->name) }}"
                placeholder="e.g. Software Development"
                style="width:100%; border:1px solid var(--line); border-radius:8px; padding:9px 12px; font-size:13px; font-family:inherit; background:var(--surface); color:var(--ink); box-sizing:border-box;"
            >
        </div>

        <div>
            <label for="description" style="display:block; font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:6px;">
                Description
            </label>
            <input
                type="text" id="description" name="description" maxlength="1000"
                value="{{ old('description', $editing?->description) }}"
                placeholder="Short description shown to users (optional)"
                style="width:100%; border:1px solid var(--line); border-radius:8px; padding:9px 12px; font-size:13px; font-family:inherit; background:var(--surface); color:var(--ink); box-sizing:border-box;"
            >
        </div>

        <div>
            <label for="office_id" style="display:block; font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:6px;">
                Office
            </label>
            <select
                id="office_id" name="office_id"
                style="width:100%; border:1px solid var(--line); border-radius:8px; padding:9px 12px; font-size:13px; font-family:inherit; background:var(--surface); color:var(--ink); box-sizing:border-box;"
            >
                <option value="">All offices (global)</option>
                @foreach ($offices as $office)
                    <option value="{{ $office->office_id }}"
                        {{ (string) old('office_id', $editing?->office_id) === (string) $office->office_id ? 'selected' : '' }}>
                        {{ $office->office_name }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn-accent">
                {{ $editing ? 'Save Changes' : 'Create' }}
            </button>

            @if ($editing)
                <a href="{{ route('admin.project-types.index') }}" class="btn" style="border:1px solid var(--line);">Cancel</a>
            @endif
        </div>
    </form>
</div>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
    <h2 class="text-lg font-bold text-slate-800">Project Types</h2>

    <form method="GET" action="{{ route('admin.project-types.index') }}" class="flex items-center space-x-2">
        <label for="office_filter" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Filter by Office:</label>
        <select name="office_id" id="office_filter" onchange="this.form.submit()"
                class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg px-3 py-1.5 font-medium shadow-sm outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
            <option value="all" {{ request('office_id') == 'all' || !request('office_id') ? 'selected' : '' }}>All Offices & Global</option>
            <option value="global" {{ request('office_id') == 'global' ? 'selected' : '' }}>Global Only (All Offices)</option>
            @foreach($offices as $office)
                <option value="{{ $office->office_id }}" {{ request('office_id') == $office->office_id ? 'selected' : '' }}>
                    {{ $office->office_name }}
                </option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th style="width:26%">Name</th>
                <th>Description</th>
                <th>Office</th>
                <th style="text-align:center; width:12%">Projects</th>
                <th style="text-align:center; width:14%">Status</th>
                <th style="width:22%; text-align:right"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($projectTypes as $type)
            <tr>
                <td class="cell-primary">{{ $type->name }}</td>
                <td class="cell-sub">{{ $type->description ?? '—' }}</td>
                <td class="cell-sub">
                    @if ($type->office)
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-50 text-blue-700">{{ $type->office->office_name }}</span>
                    @else
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-600">All Offices (Global)</span>
                    @endif
                </td>
                <td style="text-align:center">{{ $type->projects_count }}</td>
                <td style="text-align:center">
                    <span class="badge {{ $type->is_active ? 'b-active' : 'b-planning' }}">
                        <span class="badge-dot"></span>
                        {{ $type->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td style="text-align:right; white-space:nowrap;">
                    @can('manage_system_settings')
                        <a
                            href="{{ route('admin.project-types.edit', $type) }}"
                            class="link-small"
                        >Edit</a>

                        <form
                            method="POST"
                            action="{{ route('admin.project-types.toggle', $type) }}"
                            style="display:inline; margin-left:14px;"
                        >
                            @csrf
                            <button type="submit" class="link-small">
                                {{ $type->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        @unless ($type->projects_count)
                            <form
                                method="POST"
                                action="{{ route('admin.project-types.destroy', $type) }}"
                                style="display:inline; margin-left:14px;"
                                data-confirm
                                data-confirm-title="Delete project type '{{ $type->name }}'?"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-small" style="color:var(--danger);">Delete</button>
                            </form>
                        @endunless
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center; padding:30px; color:var(--ink-faint);">
                    No project types defined yet. Add the first one above.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
