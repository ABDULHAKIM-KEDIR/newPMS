@extends('layouts.app')
@section('title', 'New Project')
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('projects.index') }}">Projects</a> <b>/ New</b>
@endsection

@section('content')
<div class="page-head">
  <div><h1>New Project</h1><div class="page-sub">Creates the project with all five lifecycle phases pre-built</div></div>
</div>

@if ($errors->any())
  <div class="form-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card card-pad" style="max-width:640px;">
  <form method="POST" action="{{ route('projects.store') }}">
    @csrf
    <div class="form-field">
      <label for="project_name">Project name</label>
      <input type="text" id="project_name" name="project_name" value="{{ old('project_name') }}" required autofocus>
    </div>
    <div class="form-field">
      <label for="description">Description</label>
      <textarea id="description" name="description">{{ old('description') }}</textarea>
    </div>
    <div class="form-grid">
      <div class="form-field">
        <label for="project_type">Type</label>
        <select id="project_type" name="project_type" required>
          @foreach ($types as $t)
            <option value="{{ $t }}" {{ old('project_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-field">
        <label for="team_id">Team</label>
        <select id="team_id" name="team_id" required>
          @foreach ($teams as $team)
            <option value="{{ $team->team_id }}" {{ (string) old('team_id') === (string) $team->team_id ? 'selected' : '' }}>{{ $team->team_name }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="form-grid">
      <div class="form-field">
        <label for="start_date">Start date</label>
        <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}">
      </div>
      <div class="form-field">
        <label for="end_date">Target end date</label>
        <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}">
      </div>
    </div>
    <div class="form-field">
      <label for="allocated_amount">Budget allocated (ETB)</label>
      <input type="number" step="0.01" min="0" id="allocated_amount" name="allocated_amount" value="{{ old('allocated_amount') }}">
    </div>
    <div style="display:flex; gap:10px; margin-top:20px;">
      <button type="submit" class="btn btn-accent">Create project</button>
      <a href="{{ route('projects.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
@endsection
