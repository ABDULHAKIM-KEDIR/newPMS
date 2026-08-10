@extends('layouts.app')
@section('title', $team->team_name)
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('teams.index') }}">Teams</a> <b>/ {{ $team->team_name }}</b>
@endsection

@section('content')
@if (session('status'))
  <div class="status-alert">{{ session('status') }}</div>
@endif

<div class="page-head">
  <div>
    <h1>{{ $team->team_name }}</h1>
    <div class="page-sub">{{ $team->description }}</div>
  </div>
</div>

<div class="two-col">
  <div class="card card-pad">
    <div class="card-title-row"><h3>Members ({{ $team->members->count() }})</h3></div>
    @forelse ($team->members as $m)
      <div class="list-row">
        <div style="display:flex; align-items:center; gap:9px;">
          <div class="avatar" style="background:var(--primary-soft); color:var(--primary-dark);">{{ optional($m->user)->initials() }}</div>
          <div>
            <div style="font-weight:600;">{{ optional($m->user)->full_name }}</div>
            @if ($team->team_leader_id === $m->user_id)
              <div class="cell-sub">Team leader</div>
            @endif
          </div>
        </div>
        @if ($canManage && $team->team_leader_id !== $m->user_id)
          <form method="POST" action="{{ route('teams.members.remove', [$team, $m]) }}" onsubmit="return confirm('Remove {{ optional($m->user)->full_name }} from this team?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost" style="padding:5px 11px; font-size:11.5px; color:var(--danger); border-color:var(--danger-soft);">Remove</button>
          </form>
        @endif
      </div>
    @empty
      <div class="empty"><h4>No members yet</h4></div>
    @endforelse

    @if ($canManage)
      <form method="POST" action="{{ route('teams.members.add', $team) }}" style="display:flex; gap:8px; margin-top:16px; padding-top:16px; border-top:1px solid var(--line);">
        @csrf
        <select name="user_id" required style="flex:1; border:1px solid var(--line); border-radius:8px; padding:9px 12px; font-size:13px; font-family:inherit; background:var(--surface);">
          <option value="">Add a member…</option>
          @foreach ($availableUsers as $u)
            <option value="{{ $u->user_id }}">{{ $u->full_name }}</option>
          @endforeach
        </select>
        <button type="submit" class="btn btn-primary">Add</button>
      </form>
    @endif
  </div>

  <div class="card card-pad">
    <div class="card-title-row"><h3>Projects ({{ $team->projects->count() }})</h3></div>
    @forelse ($team->projects as $p)
      <div class="list-row">
        <a href="{{ route('projects.show', $p) }}" style="font-weight:600;">{{ $p->project_name }}</a>
        <span class="cell-sub">{{ $p->project_type }}</span>
      </div>
    @empty
      <div class="empty"><h4>No projects yet</h4></div>
    @endforelse
  </div>
</div>
@endsection
