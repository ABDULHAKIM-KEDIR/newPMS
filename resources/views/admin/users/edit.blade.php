@extends('layouts.app')
@section('title', 'Edit User')
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('admin.users.index') }}">Users</a> <b>/ {{ $user->full_name }}</b>
@endsection

@section('content')
<div class="page-head">
  <div><h1>{{ $user->full_name }}</h1><div class="page-sub">{{ $user->email }} · <span class="badge {{ $user->status === 'Active' ? 'b-active' : 'b-closed' }}"><span class="badge-dot"></span>{{ $user->status }}</span></div></div>
</div>

@if ($errors->any())
  <div class="form-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card card-pad" style="max-width:520px;">
  <form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf
    @method('PUT')
    <div class="form-field">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required autofocus>
    </div>
    <div class="form-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="form-field">
      <label for="phone">Phone</label>
      <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
    </div>
    <div class="form-field">
      <label for="role_id">Role</label>
      <select id="role_id" name="role_id" required>
        @foreach ($roles as $role)
          <option value="{{ $role->role_id }}" {{ optional($user->roles->first())->role_id === $role->role_id ? 'selected' : '' }}>{{ $role->role_name }}</option>
        @endforeach
      </select>
    </div>
    <div style="display:flex; gap:10px; margin-top:20px;">
      <button type="submit" class="btn btn-accent">Save changes</button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
@endsection
