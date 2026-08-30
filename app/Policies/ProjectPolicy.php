<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Services\RbacService;

/**
 * Object-level authorization for projects.
 *
 * Complements the slug-based Gate definitions registered in
 * AppServiceProvider: where `can:view_projects` answers "does this user
 * hold the permission anywhere", these policies answer "may this user
 * see THIS project" — resolved through the RBAC engine's project-scoped
 * sources (PM of record, assigned teams, direct project roles) plus
 * organization-wide grants.
 */
class ProjectPolicy
{
    /**
     * A user may view a project when an organization-wide grant gives them
     * view_projects, or when any project-scoped grant source (PM of record,
     * team assignment with view/contribute/manage access, direct project
     * role) includes them.
     */
    public function view(User $user, Project $project): bool
    {
        // System Administrators see everything.
        if ($user->hasPermission('manage_system_settings')) {
            return true;
        }

        // PM of record.
        if ((int) $project->pm_id === (int) $user->user_id) {
            return true;
        }

        // Member of any team assigned to the project.
        $project->loadMissing('teams.members', 'team.members');
        $memberIds = $project->teams
            ->merge([$project->team])
            ->filter()
            ->flatMap(fn ($team) => $team->members->pluck('user_id'))
            ->unique();

        if ($memberIds->contains((int) $user->user_id)) {
            return true;
        }

        return app(RbacService::class)
            ->can($user, 'view_projects', $project);
    }

    /**
     * Editing mirrors isManagedBy(): organization edit_projects, the PM of
     * record, a team leader with manage-level team assignment, or an
     * explicit project role granting edit_projects.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->isManagedBy($user);
    }

    /** Only organization-level delete_projects holders may remove a project. */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermission('delete_projects')
            && $project->isManagedBy($user);
    }
}
