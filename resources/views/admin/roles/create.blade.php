@extends('layouts.app')

@section('title', 'Create Role')

@section('crumb', '<b>Roles</b> · Create')

@section('content')

<div class="page-head">

    <div>
        <h1>Create Role</h1>
        <div class="page-sub">
            Define a new dynamic role and grant its permissions
        </div>
    </div>

    <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
        ← Back to roles
    </a>

</div>

<form
    method="POST"
    action="{{ route('admin.roles.store') }}"
>

    @csrf

    @include('admin.roles.partials._form', [
        'groupedPermissions' => $groupedPermissions,
        'allowedParents' => $allowedParents,
        'role' => null,
    ])

    <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="submit" class="btn btn-accent">
            Create role
        </button>

        <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
            Cancel
        </a>
    </div>

</form>

@endsection
