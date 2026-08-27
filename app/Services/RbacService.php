<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The single access-control evaluation engine.
 *
 * Decision model (mirrors Asana/Jira/ClickUp semantics):
 *
 * 1. Organization roles: granted to a user with no scope. Their permissions
 *    apply everywhere in the system.
 * 2. Project roles: granted to a user with scope_type='project'. Their
 *    permissions apply only inside that project (direct project membership
 *    via project_member_roles, or indirect via a team assigned to the
 *    project with the team's default project role).
 * 3. Team roles: granted with scope_type='team'; apply only inside projects
 *    that team is assigned to.
 *
 * Role inheritance: a role may declare one parent_role_id. A role grants
 * every permission of its parent chain (union), so roles can compose
 * (e.g. "Content Editor" extends "Viewer"). Cycles are rejected at write
 * time and guarded against at evaluation time.
 *
 * Evaluation is permission-first, never role-name-first: callers ask
 * "may this user edit this project?" and the service resolves it from the
 * union of org-level, project-level and team-level grants.
 */
class RbacService
{
    public const TEAM_ACCESS_LEVELS = ['view', 'contribute', 'manage'];

    /** Permissions the PM of record holds on their own project, implicitly. */
    public const PROJECT_MANAGER_BASELINE = [
        'view_projects', 'edit_projects', 'view_tasks', 'create_tasks',
        'assign_tasks', 'update_task_status', 'manage_team',
        'approve_change_requests', 'manage_budgets', 'view_reports', 'view_calendar',
    ];

    /** Per-request memo keyed by user id + project id. */
    protected array $cache = [];

    /** All effective permission slugs for a user, from every grant source. */
    public function effectivePermissions(User $user, ?Project $project = null): Collection
    {
        $key = 'u'.$user->user_id.($project ? '-p'.$project->project_id : '-org');

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $slugs = collect();

        // 1. Organization-wide roles (scope NULL on user_roles).
        $slugs = $slugs->merge($this->rolePermissionSlugs(
            $user->roles()->wherePivotNull('scope_type')->get()
        ));

        if ($project) {
            // 2. Direct project-scoped roles held by this user on this project.
            $directRoleIds = $project->memberRoles()
                ->where('user_id', $user->user_id)
                ->whereNotNull('role_id')
                ->pluck('role_id');

            $slugs = $slugs->merge($this->rolePermissionSlugs(
                Role::whereIn('role_id', $directRoleIds)->get()
            ));

            // 3. The project manager of record implicitly holds the PM
            //    baseline permissions on their own project.
            if ($project->project_manager_id && (int) $project->project_manager_id === (int) $user->user_id) {
                $slugs = $slugs->merge(self::PROJECT_MANAGER_BASELINE);
            }

            // 4. Team-derived access: for each of the user's teams assigned to
            //    this project, apply the pivot's project role (if set) plus the
            //    access-level baseline.
            foreach ($this->teamAccessFor($user, $project) as $pivot) {
                if ($pivot->project_role_id) {
                    $slugs = $slugs->merge($this->rolePermissionSlugs(
                        Role::where('role_id', $pivot->project_role_id)->get()
                    ));
                }

                $slugs = $slugs->merge($this->baselinePermissionsForAccessLevel($pivot->access_level));
            }

            // 5. Explicit team-scoped roles held by the user for any of the
            //    teams assigned to this project.
            $teamIds = $user->teams()->pluck('teams.team_id');
            if ($teamIds->isNotEmpty()) {
                $slugs = $slugs->merge($this->rolePermissionSlugs(
                    $user->roles()
                        ->wherePivot('scope_type', 'team')
                        ->whereInPivot('scope_id', $teamIds->all())
                        ->get()
                ));
            }
        }

        return $this->cache[$key] = $slugs->unique()->values();
    }

    /**
     * The central check. Organization permission is enough anywhere;
     * otherwise project-scoped sources must also grant it.
     */
    public function can(User $user, string $slug, ?Project $project = null): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $this->effectivePermissions($user, $project)->contains($slug);
    }

    /**
     * Teams of this user assigned to the project, with their access levels.
     * Returns rows shaped like project_teams pivots.
     */
    public function teamAccessFor(User $user, Project $project): Collection
    {
        $userTeamIds = $user->teams()->pluck('teams.team_id');

        if ($userTeamIds->isEmpty()) {
            return collect();
        }

        return $project->teams()
            ->whereIn('teams.team_id', $userTeamIds->all())
            ->get(['teams.team_id', 'project_teams.access_level', 'project_teams.project_role_id']);
    }

    /**
     * Minimal permission baseline implied by a team's access level —
     * used when a project-team assignment has no explicit project role.
     */
    public function baselinePermissionsForAccessLevel(string $level): array
    {
        return match ($level) {
            'manage' => [
                'view_projects', 'view_tasks', 'create_tasks', 'update_task_status',
                'assign_tasks', 'edit_projects', 'view_calendar',
            ],
            'contribute' => ['view_projects', 'view_tasks', 'update_task_status', 'view_calendar'],
            'view' => ['view_projects', 'view_tasks', 'view_calendar'],
            default => [],
        };
    }

    /** Union of each role's own permissions and its full ancestor chain. */
    public function rolePermissionSlugs(Collection $roles): Collection
    {
        return $roles->flatMap(fn (Role $role) => $this->inheritedSlugsFor($role))
            ->unique()
            ->values();
    }

    protected function inheritedSlugsFor(Role $role): Collection
    {
        $permissions = $role->relationLoaded('permissions')
            ? $role->permissions
            : $role->permissions()->get();

        $slugs = $permissions->pluck('permission_name');

        $seen = [$role->role_id => true];
        $parent = $role->parentRole()->first();
        $depth = 0;

        // Guard against cycles created outside the service (max depth 10).
        while ($parent && $depth < 10 && ! isset($seen[$parent->role_id])) {
            $seen[$parent->role_id] = true;
            $slugs = $slugs->merge($parent->permissions()->pluck('permissions.permission_name'));
            $parent = $parent->parentRole()->first();
            $depth++;
        }

        return $slugs;
    }

    /**
     * Assign a role to a user. When a scope is provided the role is only
     * effective inside that scope; pass null for organization-wide.
     */
    public function assignRole(User $user, Role $role, ?object $scope = null): void
    {
        $user->roles()->syncWithoutDetaching([
            $role->role_id => [
                'scope_type' => $scope?->getMorphClass(),
                'scope_id' => $scope?->getKey(),
            ],
        ]);

        $this->flushUser($user);
    }

    public function revokeRole(User $user, Role $role, ?object $scope = null): void
    {
        $user->roles()->newPivotStatement()
            ->where('user_id', $user->user_id)
            ->where('role_id', $role->role_id)
            ->where('scope_type', $scope?->getMorphClass())
            ->where('scope_id', $scope?->getKey())
            ->delete();

        $this->flushUser($user);
    }

    /**
     * Attach a parent role, rejecting cycles. Returns false if the link
     * would create an inheritance loop.
     */
    public function setParentRole(Role $role, ?Role $parent): bool
    {
        if ($parent === null) {
            $role->update(['parent_role_id' => null]);

            return true;
        }

        if ($parent->role_id === $role->role_id || $this->wouldCycle($role, $parent)) {
            return false;
        }

        $role->update(['parent_role_id' => $parent->role_id]);

        return true;
    }

    /** True if making $candidate an ancestor of $role closes a loop. */
    protected function wouldCycle(Role $role, Role $candidate): bool
    {
        $seen = [$role->role_id => true];
        $current = $candidate;
        $depth = 0;

        while ($current && $depth < 50) {
            if (isset($seen[$current->role_id])) {
                return true;
            }
            $seen[$current->role_id] = true;
            $current = $current->parentRole()->first();
            $depth++;
        }

        return false;
    }

    public function flushUser(User $user): void
    {
        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, 'u'.$user->user_id.'-')) {
                unset($this->cache[$key]);
            }
        }
    }
}
