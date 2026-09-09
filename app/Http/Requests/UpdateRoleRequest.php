<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for updating an existing role. The unique rule ignores the
 * role being edited; system roles keep their slug/scope (the service
 * enforces that independently of what is submitted).
 */
class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_roles');
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'role_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'role_name')->ignore($role?->role_id, 'role_id'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'scope' => ['required', 'in:'.implode(',', Role::SCOPES)],
            'parent_role_id' => ['nullable', 'integer', 'exists:roles,role_id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,permission_id'],
        ];
    }
}
