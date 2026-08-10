<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $users = User::with('roles')->get();

        return view('admin.roles', compact('roles', 'permissions', 'users'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        abort_unless(Auth::user()->isDirectorOrAdmin(), 403);

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

    public function storeRole(Request $request)
    {
        abort_unless(Auth::user()->isDirectorOrAdmin(), 403);

        $data = $request->validate([
            'role_name' => ['required', 'string', 'max:100', 'unique:roles,role_name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role = Role::create($data);

        Activity::log('Created role', 'Role', $role->role_id, $role->role_name);

        return back()->with('status', "Role \"{$role->role_name}\" created.");
    }

    public function togglePermission(Request $request, Role $role, Permission $permission)
    {
        abort_unless(Auth::user()->isDirectorOrAdmin(), 403);

        if ($role->permissions()->where('permissions.permission_id', $permission->permission_id)->exists()) {
            $role->permissions()->detach($permission->permission_id);
            $action = 'removed from';
        } else {
            $role->permissions()->attach($permission->permission_id);
            $action = 'granted to';
        }

        Activity::log('Updated role permissions', 'Role', $role->role_id, "\"{$permission->permission_name}\" {$action} {$role->role_name}");

        return back()->with('status', "Updated permissions for {$role->role_name}.");
    }
}
