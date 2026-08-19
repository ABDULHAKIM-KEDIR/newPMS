@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
  <h1 style="font-size:19px; margin-bottom:4px;">Sign in</h1>
  <div style="font-size:12.8px; color:var(--ink-soft); margin-bottom:22px;">Use your directorate account to continue.</div>

  @if ($errors->any())
    <div style="background:var(--danger-soft); color:var(--danger); border-radius:8px; padding:10px 12px; font-size:12.6px; margin-bottom:16px;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('login.attempt') }}">
    @csrf
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@ju.edu.et" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-accent" style="width:100%; justify-content:center; padding:11px;">Sign in</button>
  </form>

  <div style="text-align:center; margin-top:18px; font-size:12.6px; color:var(--ink-soft);">
    Don't have an account? Ask a System Administrator to create one for you under Users.
  </div>
@endsection
