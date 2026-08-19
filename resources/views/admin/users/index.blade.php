@extends('layouts.app')
@section('title', 'Users')
@section('crumb', '<b>Users</b>')

@section('content')
@if (session('status'))
  <div class="status-alert">{{ session('status') }}</div>
@endif

<div class="page-head">
  <div><h1>Users</h1><div class="page-sub">Everyone with an account, their role, and their status</div></div>
  <a href="{{ route('admin.users.create') }}" class="btn btn-accent">+ New User</a>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="filter-row">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email…" style="border:1px solid var(--line); border-radius:8px; padding:8px 12px; font-size:13px; font-family:inherit; background:var(--surface); width:240px;">
  <a href="{{ route('admin.users.index') }}" class="pill {{ !request('status') ? 'active' : '' }}">All</a>
  <a href="{{ route('admin.users.index', ['status' => 'Active'] + request()->only('q')) }}" class="pill {{ request('status') === 'Active' ? 'active' : '' }}">Active</a>
  <a href="{{ route('admin.users.index', ['status' => 'Inactive'] + request()->only('q')) }}" class="pill {{ request('status') === 'Inactive' ? 'active' : '' }}">Inactive</a>
  <button type="submit" class="btn btn-ghost">Search</button>
</form>

<div class="card">
  <table>
    <thead><tr><th style="width:30%">Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($users as $u)
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:9px;">
              <div class="avatar" style="background:var(--primary-soft); color:var(--primary-dark);">{{ $u->initials() }}</div>
              <span class="cell-primary">{{ $u->full_name }}</span>
            </div>
          </td>
          <td class="cell-sub">{{ $u->email }}</td>
          <td>{{ optional($u->roles->first())->role_name ?? '—' }}</td>
          <td><span class="badge {{ $u->status === 'Active' ? 'b-active' : 'b-closed' }}"><span class="badge-dot"></span>{{ $u->status }}</span></td>
          <td style="text-align:right; white-space:nowrap;">
            <a href="{{ route('admin.users.edit', $u) }}" class="link-small" style="margin-right:14px;">Edit</a>
            @if ($u->user_id !== auth()->id())
              <form method="POST" action="{{ route('admin.users.toggleStatus', $u) }}" style="display:inline;" onsubmit="return confirm('{{ $u->status === 'Active' ? 'Deactivate' : 'Activate' }} {{ $u->full_name }}?');">
                @csrf
                <button type="submit" class="link-small" style="background:none; border:none; cursor:pointer; color:{{ $u->status === 'Active' ? 'var(--danger)' : 'var(--success)' }};">
                  {{ $u->status === 'Active' ? 'Deactivate' : 'Activate' }}
                </button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h4>No users found</h4></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="margin-top:16px;">{{ $users->links() }}</div>
@endsection
