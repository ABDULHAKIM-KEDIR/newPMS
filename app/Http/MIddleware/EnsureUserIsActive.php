<?php

namespace App\Http\Middleware;

use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        /*
         * Pending registrations must never access
         * authenticated application pages.
         */
        if ($user->status === 'Pending') {

            $userId = $user->user_id;

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Activity::logAs(
                $userId,
                'Session terminated (pending approval)',
                'User',
                $userId
            );

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'Your account is still waiting for administrator approval.',
                ]);
        }

        /*
         * Rejected registrations cannot access the system.
         */
        if ($user->status === 'Rejected') {

            $userId = $user->user_id;

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Activity::logAs(
                $userId,
                'Session terminated (registration rejected)',
                'User',
                $userId
            );

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'Your registration was not approved.',
                ]);
        }

        /*
         * Existing Active / Inactive behavior.
         */
        if (! $user->isActive()) {

            $userId = $user->user_id;

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Activity::logAs(
                $userId,
                'Session terminated (deactivated)',
                'User',
                $userId
            );

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'This account has been deactivated. Contact a System Administrator.',
                ]);
        }

        return $next($request);
    }
}