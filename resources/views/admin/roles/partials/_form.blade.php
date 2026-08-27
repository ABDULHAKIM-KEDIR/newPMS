@php /** @var \Illuminate\Support\Collection $groupedPermissions */ @endphp
@php /** @var \Illuminate\Support\Collection $allowedParents */ @endphp
@php /** @var \App\Models\Role|null $role */ @endphp

<div class="role-form-grid">

    <div class="card role-form-card">

        <h2 class="section-title">{{ isset($role) ? 'Role details' : 'New role' }}</h2>

        <div class="form-field">
            <label class="form-label" for="role_name">
                Role name
            </label>

            <input
                type="text"
                id="role_name"
                name="role_name"
                value="{{ old('role_name', $role->role_name ?? '') }}"
                required
                maxlength="100"
                class="form-input"
                @if ($role?->is_system)
                    readonly
                @endif
            >

            @error('role_name')
                <div class="form-error">{{ $message }}</div>
            @enderror

            @if ($role?->is_system)
                <div class="form-hint">
                    System role — the name cannot be changed.
                </div>
            @endif
        </div>

        <div class="form-field">
            <label class="form-label" for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="3"
                maxlength="500"
                class="form-input"
            >{{ old('description', $role->description ?? '') }}</textarea>

            @error('description')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="scope">
                Scope
            </label>

            <select
                id="scope"
                name="scope"
                class="form-input"
                @if ($role?->is_system)
                    disabled
                @endif
            >
                @foreach (\App\Models\Role::SCOPES as $scopeOption)
                    <option
                        value="{{ $scopeOption }}"
                        @selected(old('scope', $role->scope ?? 'organization') === $scopeOption)
                    >
                        {{ ucfirst($scopeOption) }}
                        @if ($scopeOption === 'organization')
                            — applies system-wide
                        @elseif ($scopeOption === 'project')
                            — granted per project
                        @else
                            — granted per team
                        @endif
                    </option>
                @endforeach
            </select>

            @if ($role?->is_system)
                <input type="hidden" name="scope" value="{{ $role->scope }}">
            @endif

            @error('scope')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="parent_role_id">
                Parent role
            </label>

            <select
                id="parent_role_id"
                name="parent_role_id"
                class="form-input"
            >
                <option value="">
                    — No parent (standalone) —
                </option>

                @foreach ($allowedParents as $candidate)
                    <option
                        value="{{ $candidate->role_id }}"
                        @selected(old('parent_role_id', $role->parent_role_id ?? '') == $candidate->role_id)
                    >
                        {{ $candidate->role_name }}
                        ({{ ucfirst($candidate->scope) }})
                    </option>
                @endforeach
            </select>

            <div class="form-hint">
                The role inherits all permissions of its parent. Roles that
                would create an inheritance loop are hidden automatically.
            </div>

            @error('parent_role_id')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

    </div>

    <div class="card role-form-card">

        <h2 class="section-title">Permissions</h2>

        <div class="form-hint" style="margin-bottom:14px;">
            Directly granted permissions are checked. Permissions inherited
            from the parent role always apply, even when unchecked.
        </div>

        @error('permissions.*')
            <div class="form-error">{{ $message }}</div>
        @enderror

        @foreach ($groupedPermissions as $groupName => $groupPermissions)
            <fieldset class="perm-group">

                <legend class="perm-group-title">
                    <span>{{ $groupName }}</span>

                    <button
                        type="button"
                        class="link-small perm-group-toggle"
                        data-group="{{ \Illuminate\Support\Str::slug($groupName) }}"
                        style="background:none; border:none; cursor:pointer;"
                    >
                        Select all in group
                    </button>
                </legend>

                <div class="perm-group-grid">
                    @foreach ($groupPermissions as $permission)
                        @php
                            $directlyGranted = isset($role)
                                && $role->permissions
                                    ->contains('permission_id', $permission->permission_id);
                        @endphp

                        <label
                            class="perm-check
                                {{ $directlyGranted ? 'perm-checked' : '' }}"
                        >
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->permission_id }}"
                                @checked($directlyGranted || old('permissions', []) !== [] && in_array($permission->permission_id, old('permissions')))
                            >

                            <span>
                                <span class="perm-slug">
                                    {{ $permission->permission_name }}
                                </span>
                                <span class="perm-desc">
                                    {{ $permission->description }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

            </fieldset>
        @endforeach

    </div>

</div>

@once
<style>
    .role-form-grid {
        display: grid;
        grid-template-columns: minmax(300px, 380px) 1fr;
        gap: 20px;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .role-form-grid {
            grid-template-columns: 1fr;
        }
    }

    .section-title {
        font-size: 15px;
        margin: 0 0 16px;
    }

    .form-field {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-input {
        width: 100%;
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px;
        font-family: inherit;
        background: var(--surface, #fff);
    }

    .form-error {
        color: var(--danger, #dc2626);
        font-size: 12px;
        margin-top: 5px;
    }

    .form-hint {
        color: var(--muted, #64748b);
        font-size: 12px;
        margin-top: 5px;
    }

    .perm-group {
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 10px;
        padding: 14px 16px 16px;
        margin: 0 0 14px;
    }

    .perm-group-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px;
        font-weight: 700;
        padding: 0 4px;
        margin-bottom: 10px;
    }

    .perm-group-title .link-small {
        color: var(--primary, #2563eb);
        font-size: 12px;
        font-weight: 600;
    }

    .perm-group-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 8px;
    }

    .perm-check {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 8px;
        padding: 9px 11px;
        cursor: pointer;
        transition: border-color 0.12s, background 0.12s;
    }

    .perm-check:hover {
        border-color: var(--primary, #2563eb);
    }

    .perm-check.perm-checked {
        background: var(--primary-soft, #eff6ff);
        border-color: var(--primary, #2563eb);
    }

    .perm-check input {
        margin-top: 2px;
    }

    .perm-slug {
        display: block;
        font-size: 13px;
        font-weight: 600;
    }

    .perm-desc {
        display: block;
        font-size: 11.5px;
        color: var(--muted, #64748b);
    }
</style>
@endonce

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* "Select all in group" toggles every checkbox in its fieldset. */
    document.querySelectorAll('.perm-group-toggle').forEach(function (button) {

        button.addEventListener('click', function () {
            var fieldset = button.closest('.perm-group');
            var checkboxes = fieldset.querySelectorAll('input[type="checkbox"]');
            var allChecked = Array.from(checkboxes).every(function (cb) {
                return cb.checked;
            });

            checkboxes.forEach(function (cb) {
                cb.checked = !allChecked;
            });

            button.textContent = allChecked
                ? 'Select all in group'
                : 'Clear group';
        });

    });

    /* Highlight the label while its checkbox is checked. */
    document.querySelectorAll('.perm-check input').forEach(function (cb) {
        cb.addEventListener('change', function () {
            cb.closest('.perm-check').classList.toggle('perm-checked', cb.checked);
        });
    });

});
</script>
