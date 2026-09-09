<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

/**
 * Object-level authorization for offices.
 *
 * The System Administrator bypasses everything via Gate::before. A user who
 * holds the office head reference (head_user_id) gets view access to their
 * own office; all management actions require the manage_offices permission.
 */
class OfficePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_offices')
            || $user->hasPermission('manage_offices')
            || $user->headsAnyOffice();
    }

    public function view(User $user, Office $office): bool
    {
        return $user->hasPermission('view_offices')
            || $user->hasPermission('manage_offices')
            || $user->headsOffice($office);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_offices');
    }

    public function update(User $user, Office $office): bool
    {
        return $user->hasPermission('manage_offices');
    }

    public function delete(User $user, Office $office): bool
    {
        return $user->hasPermission('manage_offices');
    }
}
