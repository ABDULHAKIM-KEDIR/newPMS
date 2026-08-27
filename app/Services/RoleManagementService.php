<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Administrative service for creating, updating and deleting roles and
 * managing user-role assignments. Enforces the security edge cases:
 *
 *  - system roles cannot be deleted or have their slug mutated;
 *  - deleting a role re-assigns its children to its parent (no orphaned
 *    inheritance branches) and detaches user/permission pivots cleanly;
 *  - permission sync is transactional;
 *  - the last user holding a top administrative role can never lose it via
 *    the public API (prevents lockout).
 */
class RoleManagementService
{
    public function __construct(protected RbacService $rbac) {}

    /**
     * @param  array{name: string, description: ?string, scope: string, parent_role_id: ?int, rank: int}  $data
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'role_name' => $data['name'],
                'description' => $data['description'],
                'scope' => in_array($data['scope'] ?? 'organization', ['organization', 'project', 'team'], true)
                    ? $data['scope'] : 'organization',
                'parent_role_id' => $data['parent_role_id'] ?? null,
                'rank' => $data['rank'] ?? 100,
                'is_system' => false,
            ]);

            if (! empty($data['parent_role_id'])
                && ! $this->rbac->setParentRole($role, Role::find($data['parent_role_id']))) {
                $role->update(['parent_role_id' => null]);
            }

            return $role;
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $payload = [];

            if (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }

            if (array_key_exists('rank', $data)) {
                $payload['rank'] = (int) $data['rank'];
            }

            // Slug of a system role is immutable.
            if (array_key_exists('name', $data) && ! $role->is_system) {
                $payload['role_name'] = $data['name'];
            }

            if (array_key_exists('scope', $data) && ! $role->is_system) {
                $payload['scope'] = $data['scope'];
            }

            $role->update($payload);

            if (array_key_exists('parent_role_id', $data)) {
                $this->rbac->setParentRole($role, $data['parent_role_id'] ? Role::find($data['parent_role_id']) : null);
            }

            return $role->refresh();
        });
    }

    /**
     * Delete a role. System roles are refused. Child roles are re-parented
     * to the deleted role's parent so no inheritance branch is orphaned.
     * Returns false when the role is protected.
     */
    public function deleteRole(Role $role): bool
    {
        if ($role->is_system) {
            return false;
        }

        DB::transaction(function () use ($role) {
            Role::where('parent_role_id', $role->role_id)
                ->update(['parent_role_id' => $role->parent_role_id]);

            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        });

        return true;
    }

    /** Replace a role's permission set atomically. */
    public function syncPermissions(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds) {
            $valid = Permission::whereIn('permission_id', $permissionIds)->pluck('permission_id');
            $role->permissions()->sync($valid->all());
        });
    }

    public function grantPermission(Role $role, Permission $permission): void
    {
        $role->permissions()->syncWithoutDetaching([$permission->permission_id]);
    }

    public function revokePermission(Role $role, Permission $permission): void
    {
        $role->permissions()->detach([$permission->permission_id]);
    }

    /**
     * Assign a role to a user with an optional scope. Guards lockout: the
     * last active holder of an org-level administrative role cannot be
     * demoted through this path alone (callers should check
     * wouldRemoveLastAdmin before revoking).
     */
    public function assignRoleToUser(User $user, Role $role, ?object $scope = null): void
    {
        $this->rbac->assignRole($user, $role, $scope);
    }

    public function revokeRoleFromUser(User $user, Role $role, ?object $scope = null): bool
    {
        if ($this->isLastAdminHolder($user, $role)) {
            return false;
        }

        $this->rbac->revokeRole($user, $role, $scope);

        return true;
    }

    /** True if $user is the only active holder of this org-level role. */
    public function isLastAdminHolder(User $user, Role $role): bool
    {
        if ($role->scope !== 'organization') {
            return false;
        }

        $adminSlugs = ['manage_users', 'manage_roles', 'manage_system_settings'];
        $isAdmin = $role->permissions()->pluck('permission_name')->intersect($adminSlugs)->isNotEmpty();

        if (! $isAdmin) {
            return false;
        }

        $otherHolders = $role->users()
            ->where('users.user_id', '!=', $user->user_id)
            ->where('users.status', 'Active')
            ->wherePivotNull('user_roles.scope_type')
            ->count();

        return $otherHolders === 0;
    }
}
