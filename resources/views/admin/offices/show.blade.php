@extends('layouts.app')
@section('title', $office->office_name)
@section('crumb')
  <a class="link-small" href="{{ route('admin.offices.index') }}">Offices</a> <b>/ {{ $office->office_name }}</b>
@endsection

@section('content')
  <div class="page-head">
    <div>
      <h1>{{ $office->office_name }}</h1>
      <div class="page-sub">
        <span class="badge b-active">{{ $office->office_code }}</span>
        <span class="badge {{ $office->isActive() ? 'b-active' : 'b-inactive' }}">{{ $office->status }}</span>
        @if ($office->description) — {{ $office->description }} @endif
      </div>
    </div>
    @can('update', $office)
      <div style="display:flex; gap:10px;">
        <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-ghost">Edit</a>
        <form method="POST" action="{{ route('admin.offices.toggleStatus', $office) }}">
          @csrf
          <button type="submit" class="btn btn-ghost">{{ $office->isActive() ? 'Deactivate' : 'Activate' }}</button>
        </form>
      </div>
    @endcan
  </div>

  <!-- Summary stats -->
  <div class="stat-grid"
    style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px; margin-bottom:24px;">
    <div class="card card-pad" style="text-align:center;">
      <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); font-weight:700;">Head</div>
      <div style="font-weight:700; margin-top:4px;">{{ optional($office->head)->full_name ?? '—' }}</div>
    </div>
    <div class="card card-pad" style="text-align:center;">
      <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); font-weight:700;">Users</div>
      <div style="font-weight:700; margin-top:4px;">{{ $office->users->count() }}</div>
    </div>
    <div class="card card-pad" style="text-align:center;">
      <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); font-weight:700;">Teams</div>
      <div style="font-weight:700; margin-top:4px;">{{ $office->teams->count() }}</div>
    </div>
    <div class="card card-pad" style="text-align:center;">
      <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); font-weight:700;">Projects</div>
      <div style="font-weight:700; margin-top:4px;">{{ $office->allProjects()->count() }}</div>
    </div>
  </div>

  @can('view_budgets')
    <div class="card card-pad" style="margin-bottom:24px;">
      <h3 style="margin:0 0 12px; font-size:14px; text-transform:uppercase; color:var(--ink-soft);">Budget (primary-office
        projects)</h3>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:14px;">
        <div><span class="k">Total Budget</span>
          <div class="v">ETB {{ number_format($budget['allocated']) }}</div>
        </div>
        <div><span class="k">Total Spent</span>
          <div class="v">ETB {{ number_format($budget['spent']) }}</div>
        </div>
        <div><span class="k">Remaining</span>
          <div class="v">ETB {{ number_format($budget['remaining']) }}</div>
        </div>
      </div>
    </div>
  @endcan

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
    <div class="card card-pad">
      <h3 style="margin:0 0 12px; font-size:14px; text-transform:uppercase; color:var(--ink-soft);">Teams</h3>
      @forelse ($office->teams as $team)
        <a class="link-small" href="{{ route('teams.show', $team) }}"
          style="display:block; padding:6px 0; border-bottom:1px solid var(--line);">
          {{ $team->team_name }} <span style="color:var(--ink-muted); font-size:12px;">— Lead:
            {{ optional($team->leader)->full_name ?? 'Unassigned' }}</span>
        </a>
      @empty
        <div style="color:var(--ink-muted); font-size:13px;">No teams assigned to this office yet.</div>
      @endforelse
    </div>

    <div class="card card-pad">
      <h3 style="margin:0 0 12px; font-size:14px; text-transform:uppercase; color:var(--ink-soft);">Members</h3>
      @forelse ($office->users as $member)
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line);">
          <span>{{ $member->full_name }}</span>
          <span
            style="color:var(--ink-muted); font-size:12px;">{{ optional($member->roles->first())->role_name ?? 'Member' }}</span>
        </div>
      @empty
        <div style="color:var(--ink-muted); font-size:13px;">No users assigned to this office yet.</div>
      @endforelse
    </div>
  </div>

  <div class="card card-pad" style="margin-top:20px;">
    <h3 style="margin:0 0 12px; font-size:14px; text-transform:uppercase; color:var(--ink-soft);">Projects</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Project</th>
          <th>Status</th>
          <th>Role</th>
          <th>Progress</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($office->allProjects() as $project)
          <tr>
            <td><a class="link-small" href="{{ route('projects.show', $project) }}">{{ $project->project_name }}</a></td>
            <td><span class="badge">{{ ucfirst($project->status) }}</span></td>
            <td>{{ (int) $project->primary_office_id === (int) $office->office_id ? 'Primary' : 'Participating' }}</td>
            <td>{{ $project->progressPercentage() }}%</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="color:var(--ink-muted); text-align:center; padding:20px;">No projects yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection