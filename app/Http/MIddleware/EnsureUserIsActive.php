<?php

namespace App\Http\Middleware;

use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A System Administrator can deactivate a user at any moment — this makes
 * that take effect immediately for anyone with an existing session, rather
 * than only blocking the next login attempt.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->isActive()) {
            $userId = $user->user_id;
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Activity::logAs($userId, 'Session terminated (deactivated)', 'User', $userId);

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Contact a System Administrator.',
            ]);
        }

        return $next($request);
    }
}
