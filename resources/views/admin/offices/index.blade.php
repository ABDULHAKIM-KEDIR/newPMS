@extends('layouts.app')
@section('title', 'Offices')
@section('crumb')
  <b>Offices</b>
@endsection

@section('content')
  <div class="page-head">
    <div>
      <h1>Office Management</h1>
      <div class="page-sub">Directorates and offices across the organization</div>
    </div>
    @can('create', \App\Models\Office::class)
      <a href="{{ route('admin.offices.create') }}" class="btn btn-accent">+ New Office</a>
    @endcan
  </div>

  @if ($errors->any())
    <div class="form-alert">
      <ul>@foreach ($errors->all() as $e)
      <li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif
  @if (session('status'))
    <div class="form-alert" style="border-color:var(--active); color:var(--active);">{{ session('status') }}</div>
  @endif

  <div class="card" style="overflow-x:auto;">
    <table class="table">
      <thead>
        <tr>
          <th>Office</th>
          <th>Code</th>
          <th>Head</th>
          <th style="text-align:center;">Users</th>
          <th style="text-align:center;">Teams</th>
          <th style="text-align:center;">Projects</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($offices as $office)
          <tr>
            <td>
              <a class="link-small" href="{{ route('admin.offices.show', $office) }}"
                style="font-weight:600;">{{ $office->office_name }}</a>
              @if ($office->description)
                <div style="font-size:11.5px; color:var(--ink-muted);">{{ Str::limit($office->description, 60) }}</div>
              @endif
            </td>
            <td><span class="badge b-active">{{ $office->office_code }}</span></td>
            <td>{{ optional($office->head)->full_name ?? '—' }}</td>
            <td style="text-align:center;">{{ $office->users_count }}</td>
            <td style="text-align:center;">{{ $office->teams_count }}</td>
            <td style="text-align:center;">{{ $office->projects_count }}</td>
            <td>
              <span class="badge {{ $office->isActive() ? 'b-active' : 'b-inactive' }}">{{ $office->status }}</span>
            </td>
            <td style="text-align:right; white-space:nowrap;">
              <a href="{{ route('admin.offices.show', $office) }}" class="btn btn-ghost" style="padding:4px 10px;">View</a>
              @can('update', $office)
                <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-ghost" style="padding:4px 10px;">Edit</a>
                <form method="POST" action="{{ route('admin.offices.toggleStatus', $office) }}" style="display:inline;"
                  onsubmit="return confirm('Change status of {{ $office->office_name }}?')">
                  @csrf
                  <button type="submit" class="btn btn-ghost" style="padding:4px 10px;">
                    {{ $office->isActive() ? 'Deactivate' : 'Activate' }}
                  </button>
                </form>
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center; padding:32px; color:var(--ink-muted);">No offices yet. Create the
              first office to organize users, teams and projects.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection