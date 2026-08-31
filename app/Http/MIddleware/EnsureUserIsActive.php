<?php

namespace App\Http\Middleware;

use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array(strtolower($user->status ?? ''), ['inactive', 'rejected'])) {

            $userId = $user->user_id;

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Activity::logAs(
                $userId,
                'Session terminated ('.strtolower($user->status ?? '').' account)',
                'User',
                $userId
            );

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This account has been deactivated. Contact a System Administrator.'
                );
        }

        return $next($request);
    }
}
