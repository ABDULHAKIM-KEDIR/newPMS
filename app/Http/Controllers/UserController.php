<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $offices = Office::orderBy('office_name')->get();

        return view(
            'admin.users.index',
            compact('users', 'roles', 'offices')
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
        $offices = Office::orderBy('office_name')->get();

        return view(
            'admin.users.create',
            compact('roles', 'offices')
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

            'office_id' => [
                'nullable',
                'exists:offices,office_id',
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
            'office_id' => $data['office_id'] ?? null,
        ]);

        $user->roles()->sync([
            $data['role_id'],
        ]);

        Activity::log(
            'Created user',
            'User',
            $user->user_id,
            $user->full_name.' ('.$user->email.')'
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
        $offices = Office::orderBy('office_name')->get();

        return view(
            'admin.users.edit',
            compact('user', 'roles', 'offices')
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

            'office_id' => [
                'nullable',
                'exists:offices,office_id',
            ],
        ]);

        $user->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        // Office assignment / transfer (audited + user notified).
        $previousOfficeId = (int) ($user->getOriginal('office_id') ?? 0);
        $newOfficeId = (int) ($data['office_id'] ?? 0);

        if ($previousOfficeId !== $newOfficeId) {
            $user->office_id = $newOfficeId ?: null;
            $user->save();

            $oldOffice = Office::find($previousOfficeId);
            $newOffice = Office::find($newOfficeId);

            Activity::log(
                $previousOfficeId ? 'Moved user between offices' : 'Assigned user to office',
                'User',
                $user->user_id,
                $user->full_name.': '.($oldOffice?->office_name ?? 'none').' → '.($newOffice?->office_name ?? 'none')
            );

            Activity::notify(
                $user->user_id,
                'Your office assignment changed to '.($newOffice?->office_name ?? 'none'),
                'general'
            );
        }

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

            /*
             * The office is chosen by the System Administrator at
             * approval time — never by the registrant. Self-registered
             * accounts keep office_id = null until this moment.
             */
            'office_id' => [
                'nullable',
                'exists:offices,office_id',
            ],
        ]);

        $role = Role::findOrFail(
            $data['role_id']
        );

        $assignedOffice = isset($data['office_id'])
            ? Office::find($data['office_id'])
            : null;

        $user->roles()->sync([
            $role->role_id,
        ]);

        $user->role = $role->role_name;
        $user->office_id = $assignedOffice?->office_id;
        $user->status = 'Active';
        $user->save();

        Activity::log(
            'Approved user registration',
            'User',
            $user->user_id,
            "{$user->full_name} approved as {$role->role_name}"
                .($assignedOffice ? " (office: {$assignedOffice->office_name})" : ' (no office)')
        );

        Activity::notify(
            $user->user_id,
            "Your PMS account has been approved. You have been assigned the {$role->role_name} role.",
            'general'
        );

        return back()->with(
            'status',
            "{$user->full_name} was approved as {$role->role_name}."
                .($assignedOffice ? " Office: {$assignedOffice->office_name}." : '')
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
            $user->full_name.' ('.$user->email.')'
        );

        Activity::notify(
            $user->user_id,
            'Your PMS registration was not approved. Please contact a System Administrator.',
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

    /*
    |--------------------------------------------------------------------------
    | Reset User Password
    |--------------------------------------------------------------------------
    */

    public function resetPassword(Request $request, User $user)
    {
        Gate::authorize('users.reset-password');

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:64'],
        ]);

        $newPassword = $validated['password']
            ?? Str::password(10, symbols: false);

        $user->password_hash = Hash::make($newPassword);
        $user->save();

        $actor = Auth::user();

        Activity::log(
            'Reset user password',
            'User',
            $user->user_id,
            "Password reset by {$actor->full_name}"
        );

        Activity::notify(
            $user->user_id,
            'Your password was reset by an administrator. '
                .'Please change it after signing in.',
            'general'
        );

        return redirect()
            ->route('admin.users.index')
            ->with('temp_password', $newPassword)
            ->with('reset_user', $user->full_name);
    }
}
