<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuestController extends Controller
{
    /**
     * Restricted landing page for pending guest registrations.
     *
     * Guests are logged in but have not been assigned a role yet —
     * they only see read-only public system information while they
     * wait for an administrator to approve their account.
     */
    public function pendingApproval(Request $request)
    {
        $user = $request->user();

        return view('guest.pending-approval', [
            'user' => $user,
        ]);
    }
}
