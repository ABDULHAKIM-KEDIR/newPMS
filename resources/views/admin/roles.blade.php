@extends('layouts.app')
@section('title', 'Roles & Access')
@section('crumb', '<b>Roles &amp; Access</b>')

@section('content')
@if (session('status'))
  <div class="status-alert">{{ session('status') }}</div>
@endif

@if (auth()->user()->isDirectorOrAdmin())
  <div x-data="{ showNewRole: {{ $errors->has('role_name') ? 'true' : 'false' }} }">
    <div class="page-head">
      <div><h1>Roles &amp; Access</h1><div class="page-sub">Manage roles and the permissions each role carries</div></div>
      <button class="btn btn-accent" @click="showNewRole = !showNewRole">+ New Role</button>
    </div>

    <div class="card card-pad" x-show="showNewRole" x-cloak x-transition style="margin-bottom:18px; max-width:520px;">
      @if ($errors->any())
        <div class="form-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
      @endif
      <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf
        <div class="form-field">
          <label for="role_name">Role name</label>
          <input type="text" id="role_name" name="role_name" value="{{ old('role_name') }}" required autofocus>
        </div>
        <div class="form-field">
          <label for="role_description">Description</label>
          <textarea id="role_description" name="description">{{ old('description') }}</textarea>
        </div>
        <button type="submit" class="btn btn-accent">Create role</button>
      </form>
    </div>
  </div>
@else
  <div class="page-head">
    <div><h1>Roles &amp; Access</h1><div class="page-sub">Manage roles and the permissions each role carries</div></div>
  </div>
@endif

<div class="two-col">
  <div class="card">
    <table>
      <thead><tr>
        <th>Permission</th>
        @foreach ($roles as $role)
          <th style="text-align:center">{{ $role->role_name }}</th>
        @endforeach
      </tr></thead>
      <tbody>
        @foreach ($permissions as $perm)
          <tr>
            <td class="cell-primary">{{ $perm->permission_name }}</td>
            @foreach ($roles as $role)
              @php $granted = $role->permissions->contains('permission_id', $perm->permission_id); @endphp
              <td style="text-align:center;">
                @if (auth()->user()->isDirectorOrAdmin())
                  <form method="POST" action="{{ route('admin.roles.togglePermission', [$role, $perm]) }}">
                    @csrf
                    <button type="submit" style="background:none; border:none; cursor:pointer; padding:4px; font-size:14px; font-weight:700; color:{{ $granted ? 'var(--success)' : 'var(--ink-faint)' }};" title="{{ $granted ? 'Click to revoke' : 'Click to grant' }}">
                      {{ $granted ? '✓' : '–' }}
                    </button>
                  </form>
                @else
                  <span style="color:{{ $granted ? 'var(--success)' : 'var(--ink-faint)' }}; font-weight:700;">{{ $granted ? '✓' : '–' }}</span>
                @endif
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="card card-pad">
    <div class="card-title-row"><h3>Users</h3></div>
    @foreach ($users as $u)
      <div class="list-row">
        <div style="display:flex; align-items:center; gap:9px;">
          <div class="avatar" style="background:var(--primary-soft); color:var(--primary-dark);">{{ $u->initials() }}</div>
          <div style="font-weight:600;">{{ $u->full_name }}</div>
        </div>
        @if (auth()->user()->isDirectorOrAdmin())
          <form method="POST" action="{{ route('admin.roles.updateUser', $u) }}" style="display:flex; align-items:center; gap:8px;">
            @csrf
            <select name="role_id" onchange="this.form.submit()" style="border:1px solid var(--line); border-radius:6px; padding:5px 8px; font-size:12px; font-family:inherit; background:var(--surface);">
              @foreach ($roles as $role)
                <option value="{{ $role->role_id }}" {{ optional($u->roles->first())->role_id === $role->role_id ? 'selected' : '' }}>{{ $role->role_name }}</option>
              @endforeach
            </select>
          </form>
        @else
          <span class="badge b-planning"><span class="badge-dot"></span>{{ optional($u->roles->first())->role_name ?? 'No role' }}</span>
        @endif
      </div>
    @endforeach
  </div>
</div>
@endsection
