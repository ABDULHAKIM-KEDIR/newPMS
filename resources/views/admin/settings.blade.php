@extends('layouts.app')
@section('title', 'System Settings')
@section('crumb', '<b>Settings</b>')

@section('content')
@if (session('status'))
  <div class="status-alert">{{ session('status') }}</div>
@endif

<div class="page-head">
  <div><h1>System Settings</h1><div class="page-sub">Directorate-wide configuration — System Administrator only</div></div>
</div>

<div class="card card-pad" style="max-width:560px;">
  <form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')
    <div class="form-field">
      <label for="directorate_name">Directorate name</label>
      <input type="text" id="directorate_name" name="directorate_name" value="{{ old('directorate_name', $values['directorate_name'] ?? '') }}" placeholder="Jimma University ICT Directorate">
    </div>
    <div class="form-field">
      <label for="default_currency">Default currency</label>
      <input type="text" id="default_currency" name="default_currency" value="{{ old('default_currency', $values['default_currency'] ?? 'ETB') }}">
    </div>
    <div class="form-field">
      <label for="session_timeout_minutes">Session timeout notice (minutes)</label>
      <input type="number" min="5" max="1440" id="session_timeout_minutes" name="session_timeout_minutes" value="{{ old('session_timeout_minutes', $values['session_timeout_minutes'] ?? 120) }}">
    </div>
    <div class="form-field">
      <label for="support_email">Support contact email</label>
      <input type="email" id="support_email" name="support_email" value="{{ old('support_email', $values['support_email'] ?? '') }}" placeholder="ict-support@ju.edu.et">
    </div>
    <button type="submit" class="btn btn-accent" style="margin-top:8px;">Save settings</button>
  </form>
</div>
@endsection
