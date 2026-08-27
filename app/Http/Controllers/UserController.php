<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User list
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        abort_unless(
            Auth::user()->can('manage_users'),
            403
        );

        $query = User::with('roles');

        if ($q = trim((string) $request->get('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where(
                    'full_name',
                    'like',
                    "%{$q}%"
                )->orWhere(
                    'email',
                    'like',
                    "%{$q}%"
                );
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $users = $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'Pending' THEN 0
                    WHEN status = 'Active' THEN 1
                    WHEN status = 'Inactive' THEN 2
                    WHEN status = 'Rejected' THEN 3
                    ELSE 4
                 END"
            )
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('role_name')->get();

        return view(
            'admin.users.index',
            compact('users', 'roles')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Administrator-created user
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        abort_unless(
            Auth::user()->can('manage_users'),
            403
        );

        $roles = Role::orderBy('role_name')->get();

        return view(
            'admin.users.create',
            compact('roles')
        );
    }

    public function store(Request $request)
    {
        abort_unless(
            Auth::user()->can('manage_users'),
            403
        );

        $data = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email'),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'role_id' => [
                'required',
                'exists:roles,role_id',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],
        ]);

        /*
         * Users created directly by the administrator are
         * trusted and active immediately.
         */
        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'status' => 'Active',
        ]);

        $user->roles()->sync([
            $data['role_id'],
        ]);

        Activity::log(
            'Created user',
            'User',
            $user->user_id,
            $user->full_name . ' (' . $user->email . ')'
        );

        return redirect()
            ->route('admin.users.index')
            ->with(
                'status',
                "{$user->full_name} was created."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit user
    |--------------------------------------------------------------------------
    */

    public function edit(User $user)
    {
        abort_unless(
            Auth::user()->can('manage_users'),
            403
        );

        $roles = Role::orderBy('role_name')->get();

        return view(
            'admin.users.edit',
            compact('user', 'roles')
        );
    }

    public function update(Request $request, User $user)
    {
        abort_unless(
            Auth::user()->can('manage_users'),
            403
        );

        $data = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')
                    ->ignore($user->user_id, 'user_id'),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'role_id' => [
                'required',
                'exists:roles,role_id',
            ],
        ]);

        $user->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        $previousRole =
            optional($user->roles->first())->role_name
            ?? 'no role';

        $newRole = Role::findOrFail(
            $data['role_id']
        );

        if ($previousRole !== $newRole->role_name) {

            $user->roles()->sync([
                $newRole->role_id,
            ]);

            Activity::log(
                'Updated user role',
                'User',
                $user->user_id,
                "{$user->full_name}: {$previousRole} → {$newRole->role_name}"
            );

            Activity::notify(
                $user->user_id,
                "Your role was changed to {$newRole->role_name}",
                'general'
            );
        }

        Activity::log(
            'Updated user',
            'User',
            $user->user_id,
            $user->full_name
        );

        return redirect()
            ->route('admin.users.index')
            ->with(
                'status',
                "{$user->full_name} was updated."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve registration
    |--------------------------------------------------------------------------
    */

    public function approve(Request $request, User $user)
    {
        $actor = Auth::user();

        abort_unless(
            $actor->can('manage_users'),
            403
        );

        abort_unless(
            $user->status === 'Pending',
            422,
            'Only pending registrations can be approved.'
        );

        $data = $request->validate([
            'role_id' => [
                'required',
                'exists:roles,role_id',
            ],
        ]);

        $role = Role::findOrFail(
            $data['role_id']
        );

        $user->roles()->sync([
            $role->role_id,
        ]);

        $user->status = 'Active';
        $user->save();

        Activity::log(
            'Approved user registration',
            'User',
            $user->user_id,
            "{$user->full_name} approved as {$role->role_name}"
        );

        Activity::notify(
            $user->user_id,
            "Your ICT PMS account has been approved. You have been assigned the {$role->role_name} role.",
            'general'
        );

        return back()->with(
            'status',
            "{$user->full_name} was approved as {$role->role_name}."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reject registration
    |--------------------------------------------------------------------------
    */

    public function reject(User $user)
    {
        $actor = Auth::user();

        abort_unless(
            $actor->can('manage_users'),
            403
        );

        abort_unless(
            $user->status === 'Pending',
            422,
            'Only pending registrations can be rejected.'
        );

        $user->roles()->detach();

        $user->status = 'Rejected';
        $user->save();

        Activity::log(
            'Rejected user registration',
            'User',
            $user->user_id,
            $user->full_name . ' (' . $user->email . ')'
        );

        Activity::notify(
            $user->user_id,
            'Your ICT PMS registration was not approved. Please contact a System Administrator.',
            'general'
        );

        return back()->with(
            'status',
            "{$user->full_name}'s registration was rejected."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activate / deactivate existing accounts
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(User $user)
    {
        $actor = Auth::user();

        abort_unless(
            $actor->can('manage_users'),
            403
        );

        abort_if(
            $user->user_id === $actor->user_id,
            403,
            "You can't deactivate your own account."
        );

        /*
         * Pending and Rejected accounts must go through the
         * approval workflow instead of this button.
         */
        abort_if(
            in_array($user->status, ['Pending', 'Rejected']),
            422,
            'This account must be handled through the registration approval workflow.'
        );

        $user->status =
            $user->status === 'Active'
                ? 'Inactive'
                : 'Active';

        $user->save();

        Activity::log(
            $user->status === 'Active'
                ? 'Activated user'
                : 'Deactivated user',
            'User',
            $user->user_id,
            $user->full_name
        );

        return back()->with(
            'status',
            "{$user->full_name} is now {$user->status}."
        );
    }
}