<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountApproved
{
    /**
     * Routes a guest is allowed to reach while pending approval.
     */
    private const GUEST_ALLOWED = ['guest.pending', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        /*
         * Missing Accounts: the session points at a user record that
         * no longer exists (deleted/rejected by an administrator).
         */
        if (! User::find($user->user_id)) {

            $userId = $user->user_id;

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Activity::logAs(
                $userId,
                'Session terminated (account removed)',
                'User',
                $userId
            );

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your account no longer exists. Please contact a System Administrator.',
                ]);
        }

        /*
         * Guest Boundary: unapproved registrants may only see the
         * pending-approval page and log out.
         */
        if ($user->isGuest()) {

            if (in_array($request->route()->getName(), self::GUEST_ALLOWED, true)) {
                return $next($request);
            }

            return redirect()
                ->route('guest.pending')
                ->with(
                    'status',
                    'Your account is awaiting administrator review and role assignment.'
                );
        }

        /*
         * Approved Boundary: fully-approved users never see the
         * guest landing page — send them to their dashboard.
         */
        if ($request->route()->getName() === 'guest.pending') {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
