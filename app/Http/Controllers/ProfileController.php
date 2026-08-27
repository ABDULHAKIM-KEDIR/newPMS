<?php

namespace App\Http\Controllers;

use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Profile — edit / update / change password
    |--------------------------------------------------------------------------
    */

    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('roles');

        return view('profile.edit', [
            'user' => $user,
            'roleName' => optional($user->roles->first())->role_name ?? 'Member',
        ]);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
        ]);

        $user->full_name = $data['full_name'];
        $user->email = $data['email'];
        $user->save();

        Activity::log(
            'Updated profile',
            'User',
            $user->user_id,
            "{$user->full_name} updated their profile details"
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile details updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password_hash)) {
            return back()
                ->withErrors(['current_password' => 'Your current password is incorrect.'])
                ->onlyInput('current_password');
        }

        $user->password_hash = Hash::make($data['password']);
        $user->save();

        Activity::log(
            'Changed password',
            'User',
            $user->user_id,
            "{$user->full_name} changed their own password"
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Password changed successfully.');
    }
}
