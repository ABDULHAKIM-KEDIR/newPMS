<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for storing a new role.
 */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_roles');
    }

    public function rules(): array
    {
        return [
            'role_name' => ['required', 'string', 'max:100', 'unique:roles,role_name'],
            'description' => ['nullable', 'string', 'max:500'],
            'scope' => ['required', 'in:'.implode(',', Role::SCOPES)],
            'parent_role_id' => ['nullable', 'integer', 'exists:roles,role_id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,permission_id'],
        ];
    }
}
