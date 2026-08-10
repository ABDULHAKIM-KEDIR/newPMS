<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Small helper so every real action in the app (not just the seeder)
 * writes to the audit log and, where relevant, notifies someone —
 * matching what the landing page promises ("every action logged").
 */
class Activity
{
    public static function log(string $action, string $entityType, ?int $entityId, ?string $details = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
        ]);
    }

    public static function notify(int $userId, string $message, string $type = 'general'): void
    {
        // Don't notify people about their own actions.
        if ($userId === Auth::id()) {
            return;
        }

        Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'is_read' => false,
            'type' => $type,
        ]);
    }
}
