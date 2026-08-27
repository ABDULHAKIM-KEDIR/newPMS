@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('crumb', 'Roles &amp; Permissions')

@section('content')

<div class="page-head">

    <div>
        <h1>Roles &amp; Permissions</h1>
        <div class="page-sub">
            Dynamic roles, inheritance and the permission matrix
        </div>
    </div>

    @can('manage_roles')
        <a href="{{ route('admin.roles.create') }}" class="btn btn-accent">
            + New Role
        </a>
    @endcan

</div>

@if (session('status'))
    <div class="flash-status">
        {{ session('status') }}
    </div>
@endif

@error('role')
    <div class="flash-error">
        {{ $message }}
    </div>
@enderror

{{-- Quick role assignment drawer trigger handled per-role below. --}}

<div class="card">
    <table>
        <thead>
            <tr>
                <th style="width:22%">Role</th>
                <th>Scope</th>
                <th>Parent Role</th>
                <th style="text-align:center">Users</th>
                <th style="text-align:center">Permissions</th>
                <th style="width:26%; text-align:right"></th>
            </tr>
        </thead>
        <tbody>

        @forelse ($roles as $role)
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="cell-primary">{{ $role->role_name }}</span>

                        @if ($role->is_system)
                            <span
                                class="badge b-system"
                                title="System role — protected from deletion"
                            >🔒 System</span>
                        @endif
                    </div>

                    @if ($role->description)
                        <div class="cell-sub">{{ $role->description }}</div>
                    @endif
                </td>

                <td>
                    <span class="badge {{ $role->scope === 'organization' ? 'b-active' : 'b-planning' }}">
                        <span class="badge-dot"></span>
                        {{ ucfirst($role->scope) }}
                    </span>
                </td>

                <td class="cell-sub">
                    {{ $role->parentRole?->role_name ?? '—' }}
                </td>

                <td style="text-align:center">{{ $role->users_count }}</td>

                <td style="text-align:center">
                    {{ $role->permissions->count() }}
                </td>

                <td style="text-align:right; white-space:nowrap;">

                    @can('manage_roles')
                        <button
                            type="button"
                            class="link-small role-assign-open"
                            data-role-id="{{ $role->role_id }}"
                            data-role-name="{{ $role->role_name }}"
                        >
                            Assign
                        </button>

                        <a
                            href="{{ route('admin.roles.edit', $role) }}"
                            class="link-small"
                            style="margin-left:14px;"
                        >Edit</a>

                        <form
                            method="POST"
                            action="{{ route('admin.roles.destroy', $role) }}"
                            style="display:inline; margin-left:14px;"
                            onsubmit="return confirm('Delete role &quot;{{ $role->role_name }}&quot;? Its child roles will be re-parented to its parent.');"
                        >
                            @csrf
                            @method('DELETE')

                            @if ($role->is_system)
                                <span
                                    class="link-small"
                                    style="color:var(--muted); cursor:not-allowed;"
                                    title="System roles are protected"
                                >Delete</span>
                            @else
                                <button
                                    type="submit"
                                    class="link-small"
                                    style="background:none; border:none; cursor:pointer; color:var(--danger);"
                                >Delete</button>
                            @endif
                        </form>
                    @else
                        <span class="cell-sub">View only</span>
                    @endcan

                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <div class="empty">
                        <h4>No roles found</h4>
                    </div>
                </td>
            </tr>
        @endforelse

        </tbody>
    </table>
</div>

{{-- =========================================================
     ROLE ASSIGNMENT DRAWER
     ========================================================= --}}
@can('manage_roles')
    @php
        $holdersByRole = [];
        foreach ($roles as $r) {
            $holdersByRole[$r->role_id] = $r->users
                ->filter(fn ($u) => $u->pivot->scope_type === null)
                ->map(fn ($u) => [
                    'id' => $u->user_id,
                    'name' => $u->full_name,
                    'email' => $u->email,
                ])
                ->values()
                ->all();
        }
    @endphp
    <div
        id="assignDrawer"
        class="assign-drawer-overlay"
        style="display:none;"
        aria-hidden="true"
    >
        <aside class="assign-drawer" role="dialog" aria-modal="true">

            <div class="assign-drawer-head">
                <div>
                    <h3>Assign role</h3>
                    <div class="assign-drawer-role" id="assignDrawerRoleName">—</div>
                </div>
                <button
                    type="button"
                    class="pms-modal-close"
                    id="assignDrawerClose"
                    aria-label="Close"
                >✕</button>
            </div>

            <div class="assign-drawer-body">

                <form
                    method="POST"
                    id="assignDrawerForm"
                    action=""
                >
                    @csrf

                    <label class="form-label">
                        User
                        <select name="user_id" required class="form-input">
                            <option value="">Choose a user…</option>
                            @foreach ($users as $drawerUser)
                                <option value="{{ $drawerUser->user_id }}">
                                    {{ $drawerUser->full_name }} ({{ $drawerUser->email }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit" class="btn btn-accent" style="width:100%;">
                        Assign role
                    </button>

                </form>

                <div class="assign-holders">
                    <h4>Current holders</h4>
                    <div id="assignDrawerHolders" class="assign-holders-list"></div>
                </div>

            </div>

        </aside>
    </div>
@endcan

@endsection

@once
<style>
    .flash-status {
        background: var(--success-soft);
        color: var(--success);
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .flash-error {
        background: var(--danger-soft);
        color: var(--danger);
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .badge.b-system {
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 11px;
    }

    .assign-drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 90;
        display: flex;
        justify-content: flex-end;
    }

    .assign-drawer {
        width: min(420px, 100%);
        height: 100%;
        background: var(--surface, #fff);
        box-shadow: -12px 0 32px rgba(15, 23, 42, 0.2);
        display: flex;
        flex-direction: column;
        animation: drawer-in 0.18s ease-out;
    }

    @keyframes drawer-in {
        from { transform: translateX(24px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .assign-drawer-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 20px;
        border-bottom: 1px solid var(--line, #e2e8f0);
    }

    .assign-drawer-head h3 {
        margin: 0;
        font-size: 16px;
    }

    .assign-drawer-role {
        font-size: 13px;
        font-weight: 600;
        color: var(--primary, #2563eb);
    }

    .assign-drawer-body {
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .assign-holders h4 {
        font-size: 13px;
        margin: 0 0 10px;
        color: var(--muted, #64748b);
    }

    .assign-holders-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .holder-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
    }

    .holder-row .cell-sub {
        font-size: 12px;
    }

    .assign-empty {
        font-size: 13px;
        color: var(--muted, #64748b);
    }
</style>
@endonce

@can('manage_roles')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var holdersByRole = @json($holdersByRole);
    var assignRoutes = {};

    @foreach ($roles as $r)
        assignRoutes[{{ $r->role_id }}] = {
            assign: '{{ route('admin.roles.assignUser', $r) }}',
            revokeBase: '{{ url('admin/roles/' . $r->role_id . '/users') }}'
        };
    @endforeach

    var overlay = document.getElementById('assignDrawer');
    var form = document.getElementById('assignDrawerForm');
    var roleNameEl = document.getElementById('assignDrawerRoleName');
    var holdersEl = document.getElementById('assignDrawerHolders');
    var currentRoleId = null;

    function closeDrawer() {
        overlay.style.display = 'none';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        currentRoleId = null;
    }

    function renderHolders(roleId) {
        var holders = holdersByRole[roleId] || [];

        if (holders.length === 0) {
            holdersEl.innerHTML =
                '<div class="assign-empty">No users hold this role yet.</div>';
            return;
        }

        holdersEl.innerHTML = '';

        holders.forEach(function (holder) {
            var row = document.createElement('div');
            row.className = 'holder-row';

            var info = document.createElement('div');
            info.innerHTML =
                '<div><strong>' + holder.name + '</strong></div>' +
                '<div class="cell-sub">' + holder.email + '</div>';
            row.appendChild(info);

            var revokeForm = document.createElement('form');
            revokeForm.method = 'POST';
            revokeForm.action = assignRoutes[roleId].revokeBase + '/' + holder.id;
            revokeForm.innerHTML =
                '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                '<input type="hidden" name="_method" value="DELETE">';

            var revokeBtn = document.createElement('button');
            revokeBtn.type = 'submit';
            revokeBtn.className = 'link-small';
            revokeBtn.style.cssText =
                'background:none;border:none;cursor:pointer;color:var(--danger);';
            revokeBtn.textContent = 'Revoke';
            revokeBtn.addEventListener('click', function (event) {
                if (!confirm('Revoke "' + roleNameEl.textContent +
                    '" from ' + holder.name + '?')) {
                    event.preventDefault();
                }
            });

            revokeForm.appendChild(revokeBtn);
            row.appendChild(revokeForm);
            holdersEl.appendChild(row);
        });
    }

    document.querySelectorAll('.role-assign-open').forEach(function (button) {
        button.addEventListener('click', function () {
            currentRoleId = parseInt(button.dataset.roleId, 10);

            roleNameEl.textContent = button.dataset.roleName;
            form.action = assignRoutes[currentRoleId].assign;

            renderHolders(currentRoleId);

            overlay.style.display = 'flex';
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    });

    document.getElementById('assignDrawerClose')
        .addEventListener('click', closeDrawer);

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeDrawer();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

});
</script>
@endcan
