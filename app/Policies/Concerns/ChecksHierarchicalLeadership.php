<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Hierarchical leadership check for the strict 5-tier org model:
 * Department (Dept Head) -> Office (Office Head) -> Project (Project
 * Manager) -> Team (Team Lead) -> [optional] Sub-Team (Sub-Team Lead).
 *
 * A user passes when they are the direct leader of the node OR hold a
 * leadership position on any ancestor node. Each authorizable model
 * exposes leadershipUserIds() so the traversal stays on the model.
 */
trait ChecksHierarchicalLeadership
{
    protected function isDirectLeader(User $user, $record): bool
    {
        return in_array((int) $user->user_id, $record->leadershipUserIds(), true);
    }

    /**
     * Leadership on the node itself or on any parent node. Records without
     * a leadershipUserIds() method fall back to false so unrelated models
     * never accidentally authorize.
     */
    protected function leadsOrOversees(User $user, $record): bool
    {
        if (! method_exists($record, 'leadershipUserIds')) {
            return false;
        }

        return $this->isDirectLeader($user, $record);
    }
}
