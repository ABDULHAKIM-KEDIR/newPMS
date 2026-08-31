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
        style="display:grid; grid-template-columns:1fr 2fr auto; gap:12px; align-items:end;"
    >
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

<div class="card">
    <table>
        <thead>
            <tr>
                <th style="width:26%">Name</th>
                <th>Description</th>
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
                                onsubmit="return confirm('Delete project type &quot;{{ $type->name }}&quot;?');"
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
