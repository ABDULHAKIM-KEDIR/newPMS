@extends('layouts.app')
@section('title', 'Roles & Access')
@section('crumb', '<b>Roles &amp; Access</b>')

@section('content')
@if (session('status'))
  <div class="status-alert">{{ session('status') }}</div>
@endif

@php
  $tagFor = ['ICT Director' => 'DIRECTOR', 'Team Leader' => 'LEADER', 'Team Member' => 'MEMBER', 'System Administrator' => 'ADMIN'];
  $myRoleName = optional(auth()->user()->roles->first())->role_name;
@endphp

<div class="rbac-section">
  <div class="rbac-eyebrow">Role-based access</div>
  <h2>Built around your directorate's structure</h2>
  <p>Every account gets exactly the access their role needs — nothing more.</p>
</div>

<div class="rbac-cards">
  @foreach ($roleGrants as $roleName => $slugs)
    <div class="rbac-card {{ $myRoleName === $roleName ? 'is-you' : '' }}">
      <span class="rbac-tag">{{ $tagFor[$roleName] ?? strtoupper($roleName) }}</span>
      <h3>{{ $roleName }}{{ $myRoleName === $roleName ? ' (you)' : '' }}</h3>
      <ul>
        @foreach (array_slice($slugs, 0, 4) as $slug)
          <li>{{ \App\Support\Permissions::ALL[$slug] ?? $slug }}</li>
        @endforeach
      </ul>
    </div>
  @endforeach
</div>

@can('manage_roles')
  <div x-data="{ showNewRole: {{ $errors->has('role_name') ? 'true' : 'false' }} }">
    <div class="page-head">
      <div><h1>Permission matrix</h1><div class="page-sub">Click any ✓/– cell to grant or revoke a permission for that role</div></div>
      <div style="display:flex; gap:8px;">
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Manage users →</a>
        <button class="btn btn-accent" @click="showNewRole = !showNewRole">+ New Role</button>
      </div>
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
    <div><h1>Permissions</h1><div class="page-sub">What each role can currently do</div></div>
  </div>
@endcan

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
          <td class="cell-primary">{{ $perm->description ?: $perm->permission_name }}<div class="cell-sub mono">{{ $perm->permission_name }}</div></td>
          @foreach ($roles as $role)
            @php $granted = $role->permissions->contains('permission_id', $perm->permission_id); @endphp
            <td style="text-align:center;">
              @can('manage_roles')
                <form method="POST" action="{{ route('admin.roles.togglePermission', [$role, $perm]) }}">
                  @csrf
                  <button type="submit" style="background:none; border:none; cursor:pointer; padding:4px; font-size:14px; font-weight:700; color:{{ $granted ? 'var(--success)' : 'var(--ink-faint)' }};" title="{{ $granted ? 'Click to revoke' : 'Click to grant' }}">
                    {{ $granted ? '✓' : '–' }}
                  </button>
                </form>
              @else
                <span style="color:{{ $granted ? 'var(--success)' : 'var(--ink-faint)' }}; font-weight:700;">{{ $granted ? '✓' : '–' }}</span>
              @endcan
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
