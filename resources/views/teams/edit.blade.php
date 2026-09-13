@extends('layouts.app')
@section('title', 'Edit Team')
@section('crumb')
    <a class="link-small" style="cursor:pointer;" href="{{ route('teams.show', $team) }}">{{ $team->team_name }}</a> /
    Edit
@endsection

@section('content')
    <div class="page-head">
        <div>
            <h1>Edit Team</h1>
            <div class="page-sub">Update team details, leadership, and status</div>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert">
            <ul>@foreach ($errors->all() as $e)
            <li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card card-pad" style="max-width:560px;">
        <form method="POST" action="{{ route('teams.update', $team) }}">
            @csrf
            @method('PUT')
            <div class="form-field">
                <label for="team_name">Team name</label>
                <input type="text" id="team_name" name="team_name" value="{{ old('team_name', $team->team_name) }}" required
                    autofocus>
            </div>
            <div class="form-field">
                <label for="team_leader_id">Team leader <span
                        style="font-weight:400; color:var(--ink-faint);">(optional)</span></label>
                <select id="team_leader_id" name="team_leader_id">
                    <option value="">— None yet —</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->user_id }}" {{ (string) old('team_leader_id', $team->team_leader_id) === (string) $u->user_id ? 'selected' : '' }}>{{ $u->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{ old('description', $team->description) }}</textarea>
            </div>
            <div class="form-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="Active" {{ old('status', $team->status ?: 'Active') === 'Active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="Inactive" {{ old('status', $team->status ?: 'Active') === 'Inactive' ? 'selected' : '' }}>
                        Inactive
                    </option>
                </select>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-accent">Save changes</button>
                <a href="{{ route('teams.show', $team) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>

        <!-- Separate delete form (outside the main form) -->
        <div
            style="margin-top:20px; padding-top:16px; border-top:1px solid var(--line); display:flex; justify-content:flex-end;">
            <form method="POST" action="{{ route('teams.destroy', $team) }}"
                onsubmit="return confirm('Delete \'{{ $team->team_name }}\' permanently? Its projects will be detached and its members removed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost"
                    style="color:var(--danger); border-color:var(--danger-soft);">Delete
                    team</button>
            </form>
        </div>
    </div>
@endsection
