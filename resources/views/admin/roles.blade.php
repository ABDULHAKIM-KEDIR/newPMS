@extends('layouts.app')
@section('title', 'Roles & Access')
@section('crumb', '<b>Roles &amp; Access</b>')

@section('content')
<div class="page-head">
  <div><h1>Roles &amp; Access</h1><div class="page-sub">Manage roles and the permissions each role carries</div></div>
  <button class="btn btn-accent">+ New Role</button>
</div>

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
              <td style="text-align:center;">
                @if ($role->permissions->contains('permission_id', $perm->permission_id))
                  <span style="color:var(--success); font-weight:700;">✓</span>
                @else
                  <span style="color:var(--ink-faint);">–</span>
                @endif
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="card card-pad">
    <div class="card-title-row"><h3>Users</h3><span class="link-small">Invite user →</span></div>
    @foreach ($users as $u)
      <div class="list-row">
        <div style="display:flex; align-items:center; gap:9px;">
          <div class="avatar" style="background:var(--primary-soft); color:var(--primary-dark);">{{ $u->initials() }}</div>
          <div><div style="font-weight:600;">{{ $u->full_name }}</div><div class="cell-sub">{{ optional($u->roles->first())->role_name }}</div></div>
        </div>
        <span class="badge {{ $u->status === 'Active' ? 'b-active' : 'b-closed' }}"><span class="badge-dot"></span>{{ $u->status }}</span>
      </div>
    @endforeach
  </div>
</div>
@endsection
