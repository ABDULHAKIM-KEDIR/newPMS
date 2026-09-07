@extends('layouts.app')
@section('title', 'Edit Office')
@section('crumb')
  <a class="link-small" href="{{ route('admin.offices.index') }}">Offices</a> <b>/ {{ $office->office_name }}</b>
@endsection

@section('content')
  <div class="page-head">
    <div>
      <h1>Edit Office</h1>
      <div class="page-sub">{{ $office->office_name }} ({{ $office->office_code }})</div>
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
    <form method="POST" action="{{ route('admin.offices.update', $office) }}">
      @csrf
      @method('PUT')
      <div class="form-field">
        <label for="office_name">Office name <span style="color:var(--danger);">*</span></label>
        <input type="text" id="office_name" name="office_name" value="{{ old('office_name', $office->office_name) }}"
          required autofocus>
      </div>
      <div class="form-field">
        <label for="office_code">Office code <span style="color:var(--danger);">*</span></label>
        <input type="text" id="office_code" name="office_code" value="{{ old('office_code', $office->office_code) }}"
          required maxlength="20">
      </div>
      <div class="form-field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3">{{ old('description', $office->description) }}</textarea>
      </div>
      <div class="form-field">
        <label for="head_user_id">Office head <span
            style="font-weight:400; color:var(--ink-faint);">(optional)</span></label>
        <select id="head_user_id" name="head_user_id">
          <option value="">— None —</option>
          @foreach ($users as $u)
            <option value="{{ $u->user_id }}" {{ (string) old('head_user_id', $office->head_user_id) === (string) $u->user_id ? 'selected' : '' }}>{{ $u->full_name }}</option>
          @endforeach
        </select>
      </div>
      <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="submit" class="btn btn-accent">Save changes</button>
        <a href="{{ route('admin.offices.show', $office) }}" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
@endsection