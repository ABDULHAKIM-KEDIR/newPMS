<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials don\'t match any account.',
            ]);
        }

        $user = Auth::user();

        /*
         * Pending guest registrations are logged in immediately,
         * but land on a restricted page until an administrator
         * approves the account and assigns a role.
         */
        if ($user->isGuest() && $user->isPending()) {
            $request->session()->regenerate();

            return redirect()
                ->route('guest.pending')
                ->with(
                    'status',
                    'Registration submitted successfully. Your account is waiting for administrator approval.'
                );
        }

        /*
         * Any pending account (even legacy ones without the guest role)
         * goes to the pending-approval page rather than the dashboard.
         */
        if ($user->isPending()) {
            $request->session()->regenerate();

            return redirect()->route('guest.pending');
        }

        /*
         * Rejected accounts cannot sign in.
         */
        if (strtolower($user->status) === 'rejected') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your registration was not approved. Please contact a System Administrator.',
            ]);
        }

        /*
         * Inactive accounts cannot sign in.
         * Pending guests are allowed through — the guest.pending
         * check above already routed them to /pending-approval.
         */
        if (strtolower($user->status) === 'inactive') {
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
            'role' => 'guest',
            'status' => 'Pending',
        ]);

        Activity::log(
            'Submitted registration',
            'User',
            $user->user_id,
            $user->full_name.' ('.$user->email.') is awaiting approval'
        );

        /*
         * Log the guest in immediately and send them to the
         * restricted pending-approval landing page.
         */
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('guest.pending')
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
