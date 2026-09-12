<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

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
     * A user may view a project when they are a system administrator,
     * or when the project sits under their office (primary or
     * participating), or when they participate in the project itself:
     * PM of record, member of an assigned team, or a direct project
     * member role.
     */
    public function view(User $user, Project $project): bool
    {
        // System Administrators see everything.
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->officeIds()->contains(fn ($id) => $project->involvesOffice((int) $id))) {
            return true;
        }

        return $project->participatesIn($user);
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
