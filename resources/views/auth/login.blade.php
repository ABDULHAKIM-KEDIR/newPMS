@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')

    <div
        style="
            text-align:center;
            margin-bottom:20px;
        "
    >

        <div
            style="
                width:72px;
                height:72px;
                margin:0 auto 12px;
                border-radius:12px;
                background:#fff;
                border:1px solid var(--line);
                display:flex;
                align-items:center;
                justify-content:center;
                box-shadow:0 4px 14px rgba(0,103,184,.10);
                overflow:hidden;
            "
        >
            <img
                src="{{ asset('images/jimma-university-logo.png') }}"
                alt="Jimma University"
                style="
                    width:62px;
                    height:62px;
                    object-fit:contain;
                    display:block;
                "
            >
        </div>

        <div
            style="
                font-family:'Space Grotesk';
                font-weight:700;
                font-size:16px;
                color:var(--primary);
                letter-spacing:-.01em;
            "
        >
            ICT PMS
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

    <h1
        style="
            font-size:19px;
            margin-bottom:4px;
        "
    >
        Sign in
    </h1>

    <div
        style="
            font-size:12.8px;
            color:var(--ink-soft);
            margin-bottom:22px;
        "
    >
        Use your directorate account to continue.
    </div>

    @if ($errors->any())

        <div
            style="
                background:var(--danger-soft);
                color:var(--danger);
                border-radius:8px;
                padding:10px 12px;
                font-size:12.6px;
                margin-bottom:16px;
            "
        >
            {{ $errors->first() }}
        </div>

    @endif

    <form
        method="POST"
        action="{{ route('login.attempt') }}"
    >

        @csrf

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
                autofocus
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
                placeholder="••••••••"
                required
            >

        </div>

        <button
            type="submit"
            class="btn btn-primary"
            style="
                width:100%;
                justify-content:center;
                padding:11px;
            "
        >
            Sign in
        </button>

    </form>

    <div
        style="
            text-align:center;
            margin-top:18px;
            font-size:12.6px;
            color:var(--ink-soft);
        "
    >
        Don't have an account?
        Ask a System Administrator to create one for you under Users.
    </div>

@endsection