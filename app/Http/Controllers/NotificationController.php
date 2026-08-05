<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = Auth::id() ?? optional(\App\Models\User::first())->user_id;

        $notifications = Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead()
    {
        $userId = Auth::id() ?? optional(\App\Models\User::first())->user_id;
        Notification::where('user_id', $userId)->update(['is_read' => true]);

        return back();
    }
}
