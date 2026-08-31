@extends('layouts.guest')

@section('title', 'Create account')

@section('content')

    <style>
        .guest-card { width: 380px; padding: 24px 28px; }
        .field { margin-bottom: 11px; }
        .field label { margin-bottom: 4px; font-size: 12px; }
        .field input { padding: 8px 11px; font-size: 13px; }
    </style>

    <div
        style="
            display:flex;
            align-items:center;
            justify-content:center;
            gap:12px;
            margin-bottom:14px;
        "
    >

        <div
            style="
                width:54px;
                height:54px;
                margin:0;
                border-radius:12px;
                background:#fff;
                border:1px solid var(--line);
                display:flex;
                align-items:center;
                justify-content:center;
                box-shadow:0 4px 14px rgba(0,103,184,.10);
                overflow:hidden;
                flex-shrink:0;
            "
        >
            <img
                src="{{ asset('images/jimma-university-logo.png') }}"
                alt="Jimma University"
                style="
                    width:46px;
                    height:46px;
                    object-fit:contain;
                    display:block;
                "
            >
        </div>

        <div style="text-align:left;">
            <div
                style="
                    font-family:'Space Grotesk';
                    font-weight:700;
                    font-size:16px;
                    color:var(--primary);
                "
            >
                PMS
            </div>

            <div
                style="
                    font-size:11.5px;
                    color:var(--ink-soft);
                    margin-top:2px;
                "
            >
                Jimma University
            </div>
        </div>

    </div>

    <h1
        style="
            font-size:18px;
            margin-bottom:3px;
        "
    >
        Create an account
    </h1>

    <div
        style="
            font-size:12.3px;
            color:var(--ink-soft);
            margin-bottom:14px;
        "
    >
        Submit your account request for administrator approval.
    </div>

    @if ($errors->any())
        <div
            style="
                background:var(--danger-soft);
                color:var(--danger);
                border-radius:8px;
                padding:8px 12px;
                font-size:12.2px;
                margin-bottom:12px;
            "
        >
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.attempt') }}">
        @csrf

        <div class="field">
            <label for="full_name">
                Full name
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                value="{{ old('full_name') }}"
                placeholder="Your full name"
                required
                autofocus
            >
        </div>

        <div class="field">
            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@ju.edu.et"
                required
            >
        </div>

        <div class="field">
            <label for="phone">
                Phone
                <span style="font-weight:400; color:var(--ink-faint);">
                    (optional)
                </span>
            </label>

            <input
                type="text"
                id="phone"
                name="phone"
                value="{{ old('phone') }}"
                placeholder="+251..."
            >
        </div>

        <div class="field">
            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="At least 8 characters"
                required
            >
        </div>

        <div class="field">
            <label for="password_confirmation">
                Confirm password
            </label>

            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Repeat your password"
                required
            >
        </div>

        <button
            type="submit"
            class="btn btn-primary"
            style="
                width:100%;
                justify-content:center;
                padding:9px;
                margin-top:4px;
            "
        >
            Submit registration
        </button>

    </form>

    <div
        style="
            text-align:center;
            margin-top:12px;
            font-size:12.2px;
            color:var(--ink-soft);
        "
    >
        Already have an account?
        <a
            href="{{ route('login') }}"
            style="
                color:var(--primary);
                font-weight:600;
                text-decoration:none;
            "
        >
            Sign in
        </a>
    </div>

@endsection