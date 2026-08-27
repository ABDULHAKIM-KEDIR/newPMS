@extends('layouts.app')

@section('title', 'Users')

@section('crumb', 'Users')

@section('content')

<div class="page-head">

    <div>
        <h1>Users</h1>

        <div class="page-sub">
            Manage accounts, registrations, roles, and access
        </div>
    </div>

    <a
        href="{{ route('admin.users.create') }}"
        class="btn btn-accent"
    >
        + New User
    </a>

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

@if (session('temp_password'))

    <div
        id="resetPasswordModal"
        class="pms-modal-overlay"
    >
        <div
            class="pms-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="resetModalTitle"
        >

            <div class="pms-modal-top">

                <div
                    class="pms-modal-icon"
                    style="
                        background:var(--success-soft);
                        color:var(--success);
                    "
                >
                    <svg width="21" height="21" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                </div>

                <div>
                    <h3
                        id="resetModalTitle"
                        class="pms-modal-title"
                    >
                        Password reset
                    </h3>

                    <div class="pms-modal-subtitle">
                        Share this temporary password with the user.
                    </div>
                </div>

                <button
                    type="button"
                    class="pms-modal-close"
                    id="resetModalClose"
                    aria-label="Close"
                >
                    &times;
                </button>

            </div>

            <div class="pms-modal-body">

                <div class="pms-modal-warning">

                    <strong>
                        {{ session('reset_user') }}
                    </strong>

                    <span>
                        's password has been reset to:
                    </span>

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:8px;
                            margin-top:8px;
                        "
                    >

                        <code
                            id="resetTempPassword"
                            style="
                                font-size:15px;
                                font-weight:600;
                                letter-spacing:1px;
                                user-select:all;
                            "
                        >
                            {{ session('temp_password') }}
                        </code>

                        <button
                            type="button"
                            id="resetCopyBtn"
                            class="pms-modal-btn pms-modal-btn-cancel"
                            style="padding:5px 10px; font-size:12px;"
                        >
                            Copy
                        </button>

                    </div>

                </div>

            </div>

            <div class="pms-modal-actions">

                <button
                    type="button"
                    class="pms-modal-btn pms-modal-btn-confirm"
                    id="resetModalDone"
                >
                    Done
                </button>

            </div>

        </div>
    </div>

@endif

<form
    method="GET"
    action="{{ route('admin.users.index') }}"
    class="filter-row"
>

    <input
        type="text"
        name="q"
        value="{{ request('q') }}"
        placeholder="Search name or email…"
        style="
            border:1px solid var(--line);
            border-radius:8px;
            padding:8px 12px;
            font-size:13px;
            font-family:inherit;
            background:var(--surface);
            width:240px;
        "
    >

    <a
        href="{{ route('admin.users.index') }}"
        class="pill {{ !request('status') ? 'active' : '' }}"
    >
        All
    </a>

    <a
        href="{{ route('admin.users.index', ['status' => 'Pending'] + request()->only('q')) }}"
        class="pill {{ request('status') === 'Pending' ? 'active' : '' }}"
    >
        Pending
    </a>

    <a
        href="{{ route('admin.users.index', ['status' => 'Active'] + request()->only('q')) }}"
        class="pill {{ request('status') === 'Active' ? 'active' : '' }}"
    >
        Active
    </a>

    <a
        href="{{ route('admin.users.index', ['status' => 'Inactive'] + request()->only('q')) }}"
        class="pill {{ request('status') === 'Inactive' ? 'active' : '' }}"
    >
        Inactive
    </a>

    <a
        href="{{ route('admin.users.index', ['status' => 'Rejected'] + request()->only('q')) }}"
        class="pill {{ request('status') === 'Rejected' ? 'active' : '' }}"
    >
        Rejected
    </a>

    <button
        type="submit"
        class="btn btn-ghost"
    >
        Search
    </button>

</form>

<div class="card">

    <table>

        <thead>

            <tr>
                <th style="width:25%">Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th style="width:28%"></th>
            </tr>

        </thead>

        <tbody>

        @forelse ($users as $u)

            <tr>

                <td>

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:9px;
                        "
                    >

                        <div
                            class="avatar"
                            style="
                                background:var(--primary-soft);
                                color:var(--primary-dark);
                            "
                        >
                            {{ $u->initials() }}
                        </div>

                        <span class="cell-primary">
                            {{ $u->full_name }}
                        </span>

                    </div>

                </td>

                <td class="cell-sub">
                    {{ $u->email }}
                </td>

                <td>
                    {{ optional($u->roles->first())->role_name ?? 'No role assigned' }}
                </td>

                <td>

                    @php
                        $badgeClass = match($u->status) {
                            'Active' => 'b-active',
                            'Pending' => 'b-planning',
                            default => 'b-closed',
                        };
                    @endphp

                    <span class="badge {{ $badgeClass }}">

                        <span class="badge-dot"></span>

                        {{ $u->status }}

                    </span>

                </td>

                <td
                    style="
                        text-align:right;
                        white-space:nowrap;
                    "
                >

                    @if ($u->status === 'Pending')

                        <form
    method="POST"
    action="{{ route('admin.users.approve', $u) }}"
    class="approve-user-form"
    style="
        display:inline-flex;
        align-items:center;
        gap:6px;
        margin-right:8px;
    "
>
    @csrf

    <select
        name="role_id"
        class="approve-role-select"
        required
        style="
            border:1px solid var(--line);
            border-radius:7px;
            padding:6px 8px;
            font-size:12px;
            background:var(--surface);
        "
    >
        <option value="">
            Choose role
        </option>

        @foreach ($roles as $role)
            <option value="{{ $role->role_id }}">
                {{ $role->role_name }}
            </option>
        @endforeach
    </select>

    <button
        type="button"
        class="link-small approve-user-btn"
        style="
            background:none;
            border:none;
            cursor:pointer;
            color:var(--success);
            font-weight:600;
        "
    >
        Approve
    </button>
</form>
                        <form
    method="POST"
    action="{{ route('admin.users.reject', $u) }}"
    class="reject-user-form"
    style="display:inline;"
>
    @csrf

    <button
        type="button"
        class="link-small reject-user-btn"
        style="
            background:none;
            border:none;
            cursor:pointer;
            color:var(--danger);
        "
    >
        Reject
    </button>
</form>

                    @elseif ($u->user_id !== auth()->id())

                        <a
                            href="{{ route('admin.users.edit', $u) }}"
                            class="link-small"
                            style="margin-right:14px;"
                        >
                            Edit
                        </a>

                        <form
                            method="POST"
                            action="{{ route('admin.users.toggleStatus', $u) }}"
                            style="display:inline;"
                        >

                            @csrf

                            <button
                                type="button"
                                class="link-small confirm-action-btn"
                                data-confirm-title="{{ $u->status === 'Active' ? 'Deactivate user' : 'Activate user' }}"
                                data-confirm-warning="{{ $u->status === 'Active' ? 'This user will immediately lose access to the system until reactivated.' : 'This user will regain access to the system with their current role and permissions.' }}"
                                data-confirm-button="{{ $u->status === 'Active' ? 'Deactivate' : 'Activate' }}"
                                data-confirm-tone="{{ $u->status === 'Active' ? 'danger' : 'success' }}"
                                data-confirm-user="{{ $u->full_name }}"
                                style="
                                    background:none;
                                    border:none;
                                    cursor:pointer;
                                    color:{{ $u->status === 'Active' ? 'var(--danger)' : 'var(--success)' }};
                                "
                            >
                                {{ $u->status === 'Active' ? 'Deactivate' : 'Activate' }}
                            </button>

                        </form>

                        @can('users.reset-password')
                            <form
                                method="POST"
                                action="{{ route('admin.users.resetPassword', $u) }}"
                                style="display:inline; margin-left:14px;"
                            >

                                @csrf

                                <button
                                    type="button"
                                    class="link-small confirm-action-btn"
                                    data-confirm-title="Reset password"
                                    data-confirm-warning="A secure random password will be generated. You will see it once — share it with the user so they can change it on next login."
                                    data-confirm-button="Reset password"
                                    data-confirm-tone="warn"
                                    data-confirm-user="{{ $u->full_name }}"
                                    style="
                                        background:none;
                                        border:none;
                                        cursor:pointer;
                                        color:var(--primary-dark);
                                    "
                                >
                                    Reset Password
                                </button>

                            </form>
                        @endcan

                    @else

                        <a
                            href="{{ route('admin.users.edit', $u) }}"
                            class="link-small"
                        >
                            Edit
                        </a>

                    @endif

                </td>

            </tr>

        @empty

            <tr>

                <td colspan="5">

                    <div class="empty">

                        <h4>No users found</h4>

                    </div>

                </td>

            </tr>

        @endforelse

        </tbody>

    </table>

</div>

<div style="margin-top:16px;">
    {{ $users->links() }}
</div>


{{-- =========================================================
     APPROVE USER MODAL
     ========================================================= --}}

<div
    id="approveUserModal"
    class="pms-modal-overlay"
    style="display:none;"
    aria-hidden="true"
>
    <div
        class="pms-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="approveModalTitle"
    >

        <div class="pms-modal-top">

            <div class="pms-modal-icon">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M20 6L9 17l-5-5"/>
                </svg>

            </div>

            <div>

                <h3
                    id="approveModalTitle"
                    class="pms-modal-title"
                >
                    Approve registration
                </h3>

                <div class="pms-modal-subtitle">
                    You're about to activate this account.
                </div>

            </div>

            <button
                type="button"
                class="pms-modal-close"
                id="approveModalClose"
                aria-label="Close"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M18 6L6 18"/>
                    <path d="M6 6l12 12"/>
                </svg>
            </button>

        </div>


        <div class="pms-modal-body">

            <div class="pms-modal-user">

                <div
                    class="pms-modal-user-avatar"
                    id="approveModalAvatar"
                >
                    --
                </div>

                <div>

                    <div
                        class="pms-modal-user-name"
                        id="approveModalName"
                    >
                        User
                    </div>

                    <div
                        class="pms-modal-user-email"
                        id="approveModalEmail"
                    >
                        email@example.com
                    </div>

                </div>

            </div>


            <div class="pms-modal-role">

                <span class="pms-modal-role-label">
                    Assigned role
                </span>

                <span
                    class="pms-modal-role-value"
                    id="approveModalRole"
                >
                    —
                </span>

            </div>


            <div class="pms-modal-warning">

                <strong>Account activation</strong><br>

                This user will become active immediately and
                will receive the permissions associated with
                the selected role.

            </div>

        </div>


        <div class="pms-modal-actions">

            <button
                type="button"
                class="pms-modal-btn pms-modal-btn-cancel"
                id="approveModalCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="pms-modal-btn pms-modal-btn-confirm"
                id="approveModalConfirm"
            >
                Approve account
            </button>

        </div>

    </div>
</div>


{{-- =========================================================
     REJECT USER MODAL
     ========================================================= --}}

<div
    id="rejectUserModal"
    class="pms-modal-overlay"
    style="display:none;"
    aria-hidden="true"
>
    <div
        class="pms-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rejectModalTitle"
    >

        <div class="pms-modal-top">

            <div
                class="pms-modal-icon"
                style="
                    background:var(--danger-soft);
                    color:var(--danger);
                "
            >

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M18 6L6 18"/>
                    <path d="M6 6l12 12"/>
                </svg>

            </div>

            <div>

                <h3
                    id="rejectModalTitle"
                    class="pms-modal-title"
                >
                    Reject registration
                </h3>

                <div class="pms-modal-subtitle">
                    This registration request will be rejected.
                </div>

            </div>

            <button
                type="button"
                class="pms-modal-close"
                id="rejectModalClose"
                aria-label="Close"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M18 6L6 18"/>
                    <path d="M6 6l12 12"/>
                </svg>
            </button>

        </div>


        <div class="pms-modal-body">

            <div class="pms-modal-user">

                <div
                    class="pms-modal-user-avatar"
                    id="rejectModalAvatar"
                >
                    --
                </div>

                <div>

                    <div
                        class="pms-modal-user-name"
                        id="rejectModalName"
                    >
                        User
                    </div>

                    <div
                        class="pms-modal-user-email"
                        id="rejectModalEmail"
                    >
                        email@example.com
                    </div>

                </div>

            </div>


            <div
                class="pms-modal-warning"
                style="
                    background:var(--danger-soft);
                    color:var(--danger);
                "
            >

                <strong>Reject this registration?</strong><br>

                The account will remain unable to access
                the ICT PMS.

            </div>

        </div>


        <div class="pms-modal-actions">

            <button
                type="button"
                class="pms-modal-btn pms-modal-btn-cancel"
                id="rejectModalCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="pms-modal-btn"
                id="rejectModalConfirm"
                style="
                    background:var(--danger);
                    border-color:var(--danger);
                    color:#fff;
                "
            >
                Reject registration
            </button>

        </div>

    </div>
</div>


{{-- =========================================================
     DYNAMIC CONFIRMATION MODAL (Deactivate / Activate /
     Reset Password — populated per-row at click time)
     ========================================================= --}}

<div
    id="confirmActionModal"
    class="pms-modal-overlay"
    style="display:none;"
    aria-hidden="true"
>
    <div
        class="pms-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirmActionTitle"
    >

        <div class="pms-modal-top">

            <div
                class="pms-modal-icon"
                id="confirmActionIcon"
                style="
                    background:var(--danger-soft);
                    color:var(--danger);
                "
            >

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                </svg>

            </div>

            <div>

                <h3
                    id="confirmActionTitle"
                    class="pms-modal-title"
                >
                    Are you sure?
                </h3>

            </div>

        </div>

        <div class="pms-modal-body">

            <div class="pms-modal-warning">

                <strong id="confirmActionHeadline">
                    Are you sure you want to proceed?
                </strong>

                <br>

                <span id="confirmActionWarning">
                    This action cannot be undone.
                </span>

            </div>

        </div>

        <div class="pms-modal-actions">

            <button
                type="button"
                class="pms-modal-btn pms-modal-btn-cancel"
                id="confirmActionCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="pms-modal-btn pms-modal-btn-confirm"
                id="confirmActionConfirm"
            >
                Confirm
            </button>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Approve modal
    |--------------------------------------------------------------------------
    */

    const approveModal =
        document.getElementById('approveUserModal');

    const approveClose =
        document.getElementById('approveModalClose');

    const approveCancel =
        document.getElementById('approveModalCancel');

    const approveConfirm =
        document.getElementById('approveModalConfirm');

    let activeApproveForm = null;


    function closeApproveModal() {

        approveModal.style.display = 'none';
        approveModal.setAttribute('aria-hidden', 'true');

        activeApproveForm = null;

        document.body.style.overflow = '';

    }


    document.querySelectorAll('.approve-user-btn')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const form =
                    button.closest('.approve-user-form');

                const select =
                    form.querySelector('.approve-role-select');

                /*
                 * Don't open the modal until a role has
                 * actually been selected.
                 */
                if (!select.value) {

                    select.focus();

                    select.style.borderColor =
                        'var(--danger)';

                    setTimeout(function () {
                        select.style.borderColor =
                            'var(--line)';
                    }, 1200);

                    return;
                }

                const row =
                    button.closest('tr');

                const cells =
                    row.querySelectorAll('td');

                const name =
                    cells[0]?.querySelector('.cell-primary')
                        ?.textContent
                        .trim()
                    || 'User';

                const email =
                    cells[1]?.textContent.trim()
                    || '';

                const role =
                    select.options[
                        select.selectedIndex
                    ].textContent.trim();


                document.getElementById(
                    'approveModalName'
                ).textContent = name;


                document.getElementById(
                    'approveModalEmail'
                ).textContent = email;


                document.getElementById(
                    'approveModalRole'
                ).textContent = role;


                /*
                 * Generate initials.
                 */
                const initials = name
                    .split(/\s+/)
                    .filter(Boolean)
                    .slice(0, 2)
                    .map(function (part) {
                        return part.charAt(0);
                    })
                    .join('')
                    .toUpperCase();


                document.getElementById(
                    'approveModalAvatar'
                ).textContent = initials || '--';


                activeApproveForm = form;

                approveModal.style.display = 'flex';
                approveModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.style.overflow = 'hidden';

                setTimeout(function () {
                    approveConfirm.focus();
                }, 50);

            });

        });


    approveConfirm.addEventListener(
        'click',
        function () {

            if (!activeApproveForm) {
                return;
            }

            approveConfirm.disabled = true;
            approveConfirm.textContent = 'Approving…';

            activeApproveForm.submit();

        }
    );


    approveClose.addEventListener(
        'click',
        closeApproveModal
    );

    approveCancel.addEventListener(
        'click',
        closeApproveModal
    );


    /*
    |--------------------------------------------------------------------------
    | Reject modal
    |--------------------------------------------------------------------------
    */

    const rejectModal =
        document.getElementById('rejectUserModal');

    const rejectClose =
        document.getElementById('rejectModalClose');

    const rejectCancel =
        document.getElementById('rejectModalCancel');

    const rejectConfirm =
        document.getElementById('rejectModalConfirm');

    let activeRejectForm = null;


    function closeRejectModal() {

        rejectModal.style.display = 'none';
        rejectModal.setAttribute('aria-hidden', 'true');

        activeRejectForm = null;

        document.body.style.overflow = '';

    }


    document.querySelectorAll('.reject-user-btn')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const form =
                    button.closest('.reject-user-form');

                const row =
                    button.closest('tr');

                const cells =
                    row.querySelectorAll('td');

                const name =
                    cells[0]?.querySelector('.cell-primary')
                        ?.textContent
                        .trim()
                    || 'User';

                const email =
                    cells[1]?.textContent.trim()
                    || '';


                document.getElementById(
                    'rejectModalName'
                ).textContent = name;


                document.getElementById(
                    'rejectModalEmail'
                ).textContent = email;


                const initials = name
                    .split(/\s+/)
                    .filter(Boolean)
                    .slice(0, 2)
                    .map(function (part) {
                        return part.charAt(0);
                    })
                    .join('')
                    .toUpperCase();


                document.getElementById(
                    'rejectModalAvatar'
                ).textContent = initials || '--';


                activeRejectForm = form;

                rejectModal.style.display = 'flex';
                rejectModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.style.overflow = 'hidden';

                setTimeout(function () {
                    rejectConfirm.focus();
                }, 50);

            });

        });


    rejectConfirm.addEventListener(
        'click',
        function () {

            if (!activeRejectForm) {
                return;
            }

            rejectConfirm.disabled = true;
            rejectConfirm.textContent = 'Rejecting…';

            activeRejectForm.submit();

        }
    );


    rejectClose.addEventListener(
        'click',
        closeRejectModal
    );

    rejectCancel.addEventListener(
        'click',
        closeRejectModal
    );


    /*
    |--------------------------------------------------------------------------
    | Dynamic confirmation modal
    | (Deactivate / Activate / Reset Password)
    |--------------------------------------------------------------------------
    */

    const confirmModal =
        document.getElementById('confirmActionModal');

    const confirmTitle =
        document.getElementById('confirmActionTitle');

    const confirmHeadline =
        document.getElementById('confirmActionHeadline');

    const confirmWarning =
        document.getElementById('confirmActionWarning');

    const confirmIcon =
        document.getElementById('confirmActionIcon');

    const confirmCancel =
        document.getElementById('confirmActionCancel');

    const confirmButton =
        document.getElementById('confirmActionConfirm');

    const TONES = {
        danger: {
            background: 'var(--danger-soft)',
            color: 'var(--danger)',
        },
        warn: {
            background: 'var(--accent-soft, #FEF3C7)',
            color: 'var(--accent-dark, #B45309)',
        },
        success: {
            background: 'var(--success-soft, #DCFCE7)',
            color: 'var(--success, #16A34A)',
        },
    };

    let activeConfirmForm = null;


    function closeConfirmModal() {

        confirmModal.style.display = 'none';
        confirmModal.setAttribute('aria-hidden', 'true');

        activeConfirmForm = null;

        confirmButton.disabled = false;
        confirmButton.textContent = 'Confirm';

        document.body.style.overflow = '';

    }


    document.querySelectorAll('.confirm-action-btn')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                activeConfirmForm =
                    button.closest('form');

                const tone =
                    TONES[button.dataset.confirmTone]
                    || TONES.danger;

                const title =
                    button.dataset.confirmTitle;

                const user =
                    button.dataset.confirmUser;

                confirmTitle.textContent = title;

                confirmHeadline.textContent =
                    'Are you sure you want '
                    + title.toLowerCase()
                    + ' for ' + user + '?';

                confirmWarning.textContent =
                    button.dataset.confirmWarning;

                confirmIcon.style.background =
                    tone.background;

                confirmIcon.style.color =
                    tone.color;

                confirmButton.textContent =
                    button.dataset.confirmButton;

                confirmButton.style.background =
                    tone.color;

                confirmModal.style.display = 'flex';
                confirmModal.setAttribute('aria-hidden', 'false');

                document.body.style.overflow = 'hidden';

                setTimeout(function () {
                    confirmButton.focus();
                }, 50);

            });

        });


    confirmButton.addEventListener(
        'click',
        function () {

            if (!activeConfirmForm) {
                return;
            }

            confirmButton.disabled = true;

            activeConfirmForm.submit();

        }
    );


    confirmCancel.addEventListener(
        'click',
        closeConfirmModal
    );


    confirmModal.addEventListener(
        'click',
        function (event) {

            if (event.target === confirmModal) {
                closeConfirmModal();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Close on background click
    |--------------------------------------------------------------------------
    */

    approveModal.addEventListener(
        'click',
        function (event) {

            if (event.target === approveModal) {
                closeApproveModal();
            }

        }
    );


    rejectModal.addEventListener(
        'click',
        function (event) {

            if (event.target === rejectModal) {
                closeRejectModal();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Escape key
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }

            closeApproveModal();
            closeRejectModal();
            closeConfirmModal();

        }
    );

    // Password reset success modal

    const resetModal = document.getElementById('resetPasswordModal');

    if (resetModal) {

        resetModal.style.display = 'flex';
        resetModal.setAttribute('aria-hidden', 'false');

        const closeResetModal = () => {

            resetModal.style.display = 'none';
            resetModal.setAttribute('aria-hidden', 'true');

        };

        document
            .getElementById('resetModalClose')
            .addEventListener('click', closeResetModal);

        document
            .getElementById('resetModalDone')
            .addEventListener('click', closeResetModal);

        resetModal.addEventListener(
            'click',
            (event) => {

                if (event.target === resetModal) {
                    closeResetModal();
                }
            }
        );

        document
            .getElementById('resetCopyBtn')
            .addEventListener(
                'click',
                async () => {

                    const password = document
                        .getElementById('resetTempPassword')
                        .textContent
                        .trim();

                    try {

                        await navigator.clipboard.writeText(password);

                    } catch {

                        const range = document.createRange();
                        range.selectNodeContents(
                            document.getElementById('resetTempPassword')
                        );

                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        document.execCommand('copy');
                        selection.removeAllRanges();

                    }

                    const copyBtn = document.getElementById('resetCopyBtn');
                    copyBtn.textContent = 'Copied!';

                    setTimeout(() => {
                        copyBtn.textContent = 'Copy';
                    }, 1500);

                }
            );

    }

});
</script>
@endsection