<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleManagementService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Admin UI controller for the dynamic RBAC engine. All mutations are
 * delegated to RoleManagementService so the security edge cases
 * (system-role protection, last-admin lockout prevention, child
 * re-parenting on delete, cycle-free inheritance) stay in one place.
 */
class RoleController extends Controller
{
    public function __construct(protected RoleManagementService $roleManagement) {}

    public function index()
    {
        abort_unless(Gate::any(['manage_roles', 'manage_users']), 403);

        $roles = Role::query()
            ->with(['permissions', 'parentRole'])
            ->withCount('users')
            ->orderBy('rank')
            ->orderBy('role_name')
            ->get();

        $groupedPermissions = $this->groupedPermissions();
        $users = User::where('status', 'Active')->orderBy('full_name')->get();

        return view('admin.roles.index', compact('roles', 'groupedPermissions', 'users'));
    }

    public function create()
    {
        $this->authorizeManageRoles();

        $groupedPermissions = $this->groupedPermissions();
        $allowedParents = Role::orderBy('role_name')->get();

        return view('admin.roles.create', compact('groupedPermissions', 'allowedParents'));
    }

    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = $this->roleManagement->createRole([
            'name' => $validated['role_name'],
            'description' => $validated['description'] ?? null,
            'scope' => $validated['scope'],
            'parent_role_id' => $validated['parent_role_id'] ?? null,
            'rank' => 100,
        ]);

        $this->roleManagement->syncPermissions($role, $validated['permissions'] ?? []);

        Activity::log('Created role', 'Role', $role->role_id, $role->role_name);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Role \"{$role->role_name}\" created.");
    }

    public function edit(Role $role)
    {
        $this->authorizeManageRoles();

        $role->load(['permissions', 'parentRole']);
        $groupedPermissions = $this->groupedPermissions();
        $allowedParents = $this->allowedParentsFor($role);

        return view('admin.roles.edit', compact('role', 'groupedPermissions', 'allowedParents'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        $this->roleManagement->updateRole($role, [
            'name' => $validated['role_name'],
            'description' => $validated['description'] ?? null,
            'scope' => $validated['scope'],
            'parent_role_id' => $validated['parent_role_id'] ?? null,
        ]);

        $this->roleManagement->syncPermissions($role, $validated['permissions'] ?? []);

        Activity::log('Updated role', 'Role', $role->role_id, $role->role_name);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Role \"{$role->role_name}\" updated.");
    }

    public function destroy(Role $role)
    {
        $this->authorizeManageRoles();

        $name = $role->role_name;

        if (! $this->roleManagement->deleteRole($role)) {
            return back()->withErrors([
                'role' => "\"{$name}\" is a protected system role and cannot be deleted.",
            ]);
        }

        Activity::log('Deleted role', 'Role', null, $name);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Role \"{$name}\" deleted. Any child roles were re-parented to its parent.");
    }

    /**
     * Assign a role to a user (organization scope for org roles).
     */
    public function assignUser(Request $request, Role $role)
    {
        $this->authorizeManageRoles();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,user_id'],
        ]);

        $user = User::findOrFail($data['user_id']);

        $this->roleManagement->assignRoleToUser($user, $role);

        Activity::log('Assigned role', 'User', $user->user_id, "{$role->role_name} → {$user->full_name}");
        Activity::notify($user->user_id, "You were assigned the role {$role->role_name}", 'general');

        return back()->with('status', "{$role->role_name} assigned to {$user->full_name}.");
    }

    /**
     * Revoke a role from a user. Refuses when the user is the last
     * active holder of an administrative role (lockout safeguard).
     */
    public function revokeUser(Request $request, Role $role, User $user)
    {
        $this->authorizeManageRoles();

        if (! $this->roleManagement->revokeRoleFromUser($user, $role)) {
            return back()->withErrors([
                'role' => "{$user->full_name} is the last active holder of \"{$role->role_name}\" and cannot be removed from it.",
            ]);
        }

        Activity::log('Revoked role', 'User', $user->user_id, "{$role->role_name} ✕ {$user->full_name}");

        return back()->with('status', "{$role->role_name} revoked from {$user->full_name}.");
    }

    /**
     * Keep the legacy single-role-per-user endpoint working for the
     * users admin page.
     */
    public function updateUserRole(Request $request, User $user)
    {
        Gate::authorize('manage_users');

        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        $newRole = Role::findOrFail($data['role_id']);
        $previousRole = optional($user->roles->first())->role_name ?? 'no role';

        // Every account carries exactly one directorate-wide role.
        $user->roles()->sync([$newRole->role_id]);

        Activity::log('Updated user role', 'User', $user->user_id, "{$user->full_name}: {$previousRole} → {$newRole->role_name}");
        Activity::notify($user->user_id, "Your role was changed to {$newRole->role_name}", 'general');

        return back()->with('status', "{$user->full_name}'s role updated to {$newRole->role_name}.");
    }

    public function togglePermission(Request $request, Role $role, Permission $permission)
    {
        $this->authorizeManageRoles();

        if ($role->permissions()->where('permissions.permission_id', $permission->permission_id)->exists()) {
            $this->roleManagement->revokePermission($role, $permission);
            $action = 'removed from';
        } else {
            $this->roleManagement->grantPermission($role, $permission);
            $action = 'granted to';
        }

        Activity::log('Updated role permissions', 'Role', $role->role_id, "\"{$permission->permission_name}\" {$action} {$role->role_name}");

        return back()->with('status', "Updated permissions for {$role->role_name}.");
    }

    protected function authorizeManageRoles(): void
    {
        Gate::authorize('manage_roles');
    }

    /**
     * All catalogue permissions grouped by their `group` column for the
     * permission matrix.
     *
     * @return array<string, \Illuminate\Support\Collection<int, Permission>>
     */
    protected function groupedPermissions(): array
    {
        return Permission::orderBy('permission_name')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->group ?: 'Other')
            ->all();
    }

    /**
     * Candidate parent roles for $role: everything except itself and its
     * own descendants, so selecting a parent can never close a cycle.
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    protected function allowedParentsFor(Role $role)
    {
        $excluded = collect([$role->role_id]);
        $frontier = [$role->role_id];
        $depth = 0;

        while ($frontier !== [] && $depth < 50) {
            $children = Role::whereIn('parent_role_id', $frontier)->pluck('role_id');
            $frontier = $children->diff($excluded)->values()->all();
            $excluded = $excluded->merge($children)->unique();
            $depth++;
        }

        return Role::whereNotIn('role_id', $excluded->all())
            ->orderBy('role_name')
            ->get();
    }
}