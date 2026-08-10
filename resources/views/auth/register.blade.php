@extends('layouts.guest')
@section('title', 'Create account')

@section('content')
  <h1 style="font-size:19px; margin-bottom:4px;">Create your account</h1>
  <div style="font-size:12.8px; color:var(--ink-soft); margin-bottom:22px;">New accounts start as a Team Member — an ICT Director can change your role afterwards.</div>

  @if ($errors->any())
    <div style="background:var(--danger-soft); color:var(--danger); border-radius:8px; padding:10px 12px; font-size:12.6px; margin-bottom:16px;">
      <ul style="margin:0; padding-left:16px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  
  <form method="POST" action="{{ route('register.attempt') }}">
    @csrf
    <div class="field">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" placeholder="Abebe Kebede" required autofocus>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@ju.edu.et" required>
    </div>
    <div class="field">
      <label for="phone">Phone <span style="font-weight:400; color:var(--ink-faint);">(optional)</span></label>
      <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+251 9…">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
    </div>
    <div class="field">
      <label for="password_confirmation">Confirm password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat your password" required>
    </div>
    <button type="submit" class="btn btn-accent" style="width:100%; justify-content:center; padding:11px;">Create account</button>
  </form>

  <div style="text-align:center; margin-top:18px; font-size:12.6px; color:var(--ink-soft);">
    Already have an account? <a href="{{ route('login') }}" style="color:var(--primary); font-weight:600;">Sign in</a>
  </div>
@endsection
