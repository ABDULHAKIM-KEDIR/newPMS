@extends('layouts.app')
@section('title', 'New Office')
@section('crumb')
  <a class="link-small" href="{{ route('admin.offices.index') }}">Offices</a> <b>/ New</b>
@endsection

@section('content')
  <div class="page-head">
    <div>
      <h1>New Office</h1>
      <div class="page-sub">Create a directorate or office in the organization</div>
    </div>
  </div>

  @if ($errors->any())
    <div class="form-alert">
      <ul>@foreach ($errors->all() as $e)
      <li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="card card-pad" style="max-width:560px;">
    <form method="POST" action="{{ route('admin.offices.store') }}">
      @csrf
      <div class="form-field">
        <label for="office_name">Office name <span style="color:var(--danger);">*</span></label>
        <input type="text" id="office_name" name="office_name" value="{{ old('office_name') }}" required autofocus
          placeholder="e.g. ICT Directorate">
      </div>
      <div class="form-field">
        <label for="office_code">Office code <span style="color:var(--danger);">*</span></label>
        <input type="text" id="office_code" name="office_code" value="{{ old('office_code') }}" required maxlength="20"
          placeholder="e.g. ICT">
      </div>
      <div class="form-field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
      </div>
      <div class="form-field">
        <label for="head_user_id">Office head <span
            style="font-weight:400; color:var(--ink-faint);">(optional)</span></label>
        <select id="head_user_id" name="head_user_id">
          <option value="">— None yet —</option>
          @foreach ($users as $u)
            <option value="{{ $u->user_id }}" {{ (string) old('head_user_id') === (string) $u->user_id ? 'selected' : '' }}>
              {{ $u->full_name }}</option>
          @endforeach
        </select>
      </div>
      <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="submit" class="btn btn-accent">Create office</button>
        <a href="{{ route('admin.offices.index') }}" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
@endsection