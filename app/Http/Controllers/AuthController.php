<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials don\'t match any account.',
            ]);
        }

        $user = Auth::user();

        /*
         * Pending accounts must be approved first.
         */
        if ($user->status === 'Pending') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is waiting for administrator approval.',
            ]);
        }

        /*
         * Rejected accounts cannot sign in.
         */
        if ($user->status === 'Rejected') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your registration was not approved. Please contact a System Administrator.',
            ]);
        }

        /*
         * Inactive accounts cannot sign in.
         */
        if (! $user->isActive()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact a System Administrator.',
            ]);
        }

        $request->session()->regenerate();

        Activity::logAs(
            $user->user_id,
            'Logged in',
            'User',
            $user->user_id
        );

        return redirect()->intended(route('dashboard'));
    }

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    */

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
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

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
         * Public registrations NEVER choose their own role.
         *
         * The account starts as Pending and has no role.
         * A System Administrator must approve it and assign
         * the appropriate role.
         */
        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'status' => 'Pending',
        ]);

        Activity::log(
            'Submitted registration',
            'User',
            $user->user_id,
            $user->full_name . ' (' . $user->email . ') is awaiting approval'
        );

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Registration submitted successfully. Your account is waiting for administrator approval.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Activity::logAs(
            $userId,
            'Logged out',
            'User',
            $userId
        );

        return redirect()->route('login');
    }
}