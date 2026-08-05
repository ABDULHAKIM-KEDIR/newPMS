<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')->orderByDesc('timestamp')->paginate(25);

        return view('admin.audit', compact('logs'));
    }
}
