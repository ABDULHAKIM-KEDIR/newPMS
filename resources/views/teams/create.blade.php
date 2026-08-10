@extends('layouts.app')
@section('title', 'New Team')
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('teams.index') }}">Teams</a> <b>/ New</b>
@endsection

@section('content')
<div class="page-head">
  <div><h1>New Team</h1></div>
</div>

@if ($errors->any())
  <div class="form-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card card-pad" style="max-width:560px;">
  <form method="POST" action="{{ route('teams.store') }}">
    @csrf
    <div class="form-field">
      <label for="team_name">Team name</label>
      <input type="text" id="team_name" name="team_name" value="{{ old('team_name') }}" required autofocus>
    </div>
    <div class="form-field">
      <label for="team_leader_id">Team leader <span style="font-weight:400; color:var(--ink-faint);">(optional)</span></label>
      <select id="team_leader_id" name="team_leader_id">
        <option value="">— None yet —</option>
        @foreach ($users as $u)
          <option value="{{ $u->user_id }}" {{ (string) old('team_leader_id') === (string) $u->user_id ? 'selected' : '' }}>{{ $u->full_name }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-field">
      <label for="description">Description</label>
      <textarea id="description" name="description">{{ old('description') }}</textarea>
    </div>
    <div style="display:flex; gap:10px; margin-top:20px;">
      <button type="submit" class="btn btn-accent">Create team</button>
      <a href="{{ route('teams.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
@endsection
