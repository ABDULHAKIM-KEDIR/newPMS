<?php

namespace App\Http\Controllers;

use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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

        if (! $user->isActive()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact a System Administrator.',
            ]);
        }

        $request->session()->regenerate();

        Activity::logAs($user->user_id, 'Logged in', 'User', $user->user_id);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Activity::logAs($userId, 'Logged out', 'User', $userId);

        return redirect()->route('login');
    }
}
