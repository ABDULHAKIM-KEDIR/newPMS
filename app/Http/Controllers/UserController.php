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
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('manage_users'), 403);

        $query = User::with('roles');

        if ($q = trim((string) $request->get('q', ''))) {
            $query->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('full_name')->paginate(20)->withQueryString();
        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        abort_unless(Auth::user()->can('manage_users'), 403);

        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage_users'), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,role_id'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'status' => 'Active',
        ]);

        $user->roles()->attach($data['role_id']);

        Activity::log('Created user', 'User', $user->user_id, $user->full_name . ' (' . $user->email . ')');

        return redirect()->route('admin.users.index')->with('status', "{$user->full_name} was created.");
    }

    public function edit(User $user)
    {
        abort_unless(Auth::user()->can('manage_users'), 403);

        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(Auth::user()->can('manage_users'), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        $user->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        $previousRole = optional($user->roles->first())->role_name ?? 'no role';
        $newRole = Role::findOrFail($data['role_id']);
        if ($previousRole !== $newRole->role_name) {
            $user->roles()->sync([$newRole->role_id]);
            Activity::log('Updated user role', 'User', $user->user_id, "{$user->full_name}: {$previousRole} → {$newRole->role_name}");
            Activity::notify($user->user_id, "Your role was changed to {$newRole->role_name}", 'general');
        }

        Activity::log('Updated user', 'User', $user->user_id, $user->full_name);

        return redirect()->route('admin.users.index')->with('status', "{$user->full_name} was updated.");
    }

    public function toggleStatus(User $user)
    {
        $actor = Auth::user();
        abort_unless($actor->can('manage_users'), 403);
        abort_if($user->user_id === $actor->user_id, 403, "You can't deactivate your own account.");

        $user->status = $user->status === 'Active' ? 'Inactive' : 'Active';
        $user->save();

        Activity::log(
            $user->status === 'Active' ? 'Activated user' : 'Deactivated user',
            'User',
            $user->user_id,
            $user->full_name
        );

        return back()->with('status', "{$user->full_name} is now {$user->status}.");
    }
}
