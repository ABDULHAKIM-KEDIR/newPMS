@extends('layouts.app')

@section('title', 'My Profile')

@section('crumb', 'My Profile')

@section('content')

<div class="page-head">

    <div>
        <h1>My Profile</h1>

        <div class="page-sub">
            Manage your account details and password
        </div>
    </div>

</div>

@if (session('status'))

    <div
        style="
            background:var(--success-soft);
            color:var(--success);
            border-radius:8px;
            padding:10px 12px;
            font-size:13px;
            margin-bottom:16px;
        "
    >
        {{ session('status') }}
    </div>

@endif

<div
    style="
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));
        gap:16px;
        align-items:start;
    "
>

    {{-- Profile Details --}}
    <div class="card">

        <div class="card-pad" style="padding-bottom:0;">

            <div class="card-title-row">
                <h3>Profile details</h3>
            </div>

        </div>

        <div class="card-pad">

            <form
                method="POST"
                action="{{ route('profile.update') }}"
            >

                @csrf
                @method('PUT')

                <div class="field">

                    <label for="full_name">Full name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="{{ old('full_name', $user->full_name) }}"
                        required
                    >

                    @error('full_name')
                        <div style="color:var(--danger); font-size:12px; margin-top:4px;">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <div class="field">

                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                    >

                    @error('email')
                        <div style="color:var(--danger); font-size:12px; margin-top:4px;">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <div class="field-row" style="margin-bottom:16px;">

                    <span class="k">Role</span>
                    <span class="v">{{ $roleName }}</span>

                </div>

                <div class="field-row" style="margin-bottom:16px;">

                    <span class="k">Joined</span>
                    <span class="v">{{ \Illuminate\Support\Carbon::parse($user->created_at)->format('M j, Y') }}</span>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save changes
                </button>

            </form>

        </div>

    </div>

    {{-- Change Password --}}
    <div class="card">

        <div class="card-pad" style="padding-bottom:0;">

            <div class="card-title-row">
                <h3>Change password</h3>
            </div>

        </div>

        <div class="card-pad">

            <form
                method="POST"
                action="{{ route('profile.password') }}"
            >

                @csrf
                @method('PUT')

                <div class="field">

                    <label for="current_password">Current password</label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                    >

                    @error('current_password')
                        <div style="color:var(--danger); font-size:12px; margin-top:4px;">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <div class="field">

                    <label for="password">New password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                    >

                    @error('password')
                        <div style="color:var(--danger); font-size:12px; margin-top:4px;">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <div class="field">

                    <label for="password_confirmation">Confirm new password</label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update password
                </button>

            </form>

        </div>

    </div>

</div>

@endsection
