@extends('layouts.app')
@section('title', 'New User')
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('admin.users.index') }}">Users</a> <b>/ New</b>
@endsection

@section('content')
<div class="page-head">
  <div><h1>New User</h1><div class="page-sub">Create an account directly — they can sign in immediately</div></div>
</div>

@if ($errors->any())
  <div class="form-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card card-pad" style="max-width:520px;">
  <form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="form-field">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required autofocus>
    </div>
    <div class="form-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div class="form-field">
      <label for="phone">Phone <span style="font-weight:400; color:var(--ink-faint);">(optional)</span></label>
      <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
    </div>
    <div class="form-field">
      <label for="role_id">Role</label>
      <select id="role_id" name="role_id" required>
        @foreach ($roles as $role)
          <option value="{{ $role->role_id }}" {{ (string) old('role_id') === (string) $role->role_id ? 'selected' : '' }}>{{ $role->role_name }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-field">
      <label for="password">Temporary password</label>
      <input type="text" id="password" name="password" placeholder="At least 8 characters" required>
    </div>
    <div style="display:flex; gap:10px; margin-top:20px;">
      <button type="submit" class="btn btn-accent">Create user</button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
@endsection
