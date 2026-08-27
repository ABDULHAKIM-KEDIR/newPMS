@extends('layouts.app')

@section('title', 'Edit Role')

@section('crumb', '<b>Roles</b> · Edit')

@section('content')

<div class="page-head">

    <div>
        <h1>Edit Role</h1>
        <div class="page-sub">
            {{ $role->role_name }}@if ($role->is_system)
                · protected system role
            @endif
        </div>
    </div>

    <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
        ← Back to roles
    </a>

</div>

<form
    method="POST"
    action="{{ route('admin.roles.update', $role) }}"
>

    @csrf
    @method('PUT')

    @include('admin.roles.partials._form', [
        'groupedPermissions' => $groupedPermissions,
        'allowedParents' => $allowedParents,
        'role' => $role,
    ])

    <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="submit" class="btn btn-accent">
            Save changes
        </button>

        <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
            Cancel
        </a>
    </div>

</form>

@endsection
