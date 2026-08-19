<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->can('view_audit_logs'), 403);

        $logs = AuditLog::with('user')->orderByDesc('timestamp')->paginate(25);

        return view('admin.audit', compact('logs'));
    }

    public function export(): StreamedResponse
    {
        abort_unless(Auth::user()->can('view_audit_logs'), 403);

        $filename = 'audit-log-' . now()->format('Y-m-d-His') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Timestamp', 'User', 'Action', 'Entity type', 'Entity ID', 'Details', 'IP address']);

            AuditLog::with('user')->orderByDesc('timestamp')->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $log) {
                    fputcsv($handle, [
                        $log->timestamp?->toDateTimeString(),
                        optional($log->user)->full_name ?? 'System',
                        $log->action,
                        $log->entity_type,
                        $log->entity_id,
                        $log->details,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
