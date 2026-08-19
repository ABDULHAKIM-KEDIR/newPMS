<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Small helper so every real action in the app (not just the seeder)
 * writes to the audit log and, where relevant, notifies someone —
 * matching what the landing page promises ("every action logged").
 */
class Activity
{
    public static function log(string $action, string $entityType, ?int $entityId, ?string $details = null): void
    {
        self::logAs(Auth::id(), $action, $entityType, $entityId, $details);
    }

    /**
     * Same as log(), but lets the caller specify the acting user explicitly —
     * needed for logout, where Auth::id() is already null by the time we log.
     */
    public static function logAs(?int $userId, string $action, string $entityType, ?int $entityId, ?string $details = null): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => Request::ip(),
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
