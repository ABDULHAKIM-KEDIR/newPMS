<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Policies\Concerns\ChecksHierarchicalLeadership;

/**
 * Object-level authorization for teams: the Team Lead of the team, or any
 * leadership role on a parent node (Project Manager, Office Head,
 * Department Head) may manage the team. Members and office staff can view.
 */
class TeamPolicy
{
    use ChecksHierarchicalLeadership;

    public function view(User $user, Team $team): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->leadsOrOversees($user, $team)) {
            return true;
        }

        // Members of the team, and staff of the owning office/department.
        return $team->members->contains('user_id', $user->user_id)
            || $user->officeIds()->contains((int) $team->office_id);
    }

    public function update(User $user, Team $team): bool
    {
        if ($this->headsTeamOffice($user, $team)) {
            return true;
        }

        if ($user->hasPermission('edit_teams')) {
            return true;
        }

        return $this->leadsOrOversees($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasPermission('delete_teams')
            && ($user->isAdmin()
                || $this->headsTeamOffice($user, $team)
                || $this->leadsOrOversees($user, $team));
    }

    /** Only leaders up the chain may add/remove team members. */
    public function manageMembers(User $user, Team $team): bool
    {
        if ($this->headsTeamOffice($user, $team)) {
            return true;
        }

        if ($user->hasPermission('edit_teams')) {
            return true;
        }

        return $this->leadsOrOversees($user, $team);
    }

    /** Creating a sub-team under this team: Team Lead or above. */
    public function createSubTeam(User $user, Team $team): bool
    {
        return $this->leadsOrOversees($user, $team)
            || $this->headsTeamOffice($user, $team);
    }

    /**
     * True when this user heads the office the team belongs to — the head
     * manages only teams that belong to their own office(s).
     */
    protected function headsTeamOffice(User $user, Team $team): bool
    {
        return (bool) $team->office_id
            && $user->isOfficeHead()
            && $user->headOfficeIds()->contains((int) $team->office_id);
    }
}
