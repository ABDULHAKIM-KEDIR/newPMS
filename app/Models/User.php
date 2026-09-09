<?php

namespace App\Models;

use App\Services\RbacService;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = ['full_name', 'email', 'password_hash', 'phone', 'department', 'avatar', 'status', 'role', 'office_id'];

    protected $hidden = ['password_hash'];

    /**
     * The users table stores the hash in `password_hash`, not Laravel's
     * default `password` column — point the auth system at it.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')->withPivot('joined_date');
    }

    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'project_manager_id', 'user_id');
    }

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'team_leader_id', 'user_id');
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class, 'user_id', 'user_id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to', 'user_id');
    }

    public function initials(): string
    {
        $parts = explode(' ', trim($this->full_name));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    /**
     * True if the user carries the given role. When $project is provided,
     * project/team-scoped role assignments for that project also count.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('role_name', $roleName);
    }

    /**
     * Every permission this user's org roles grant, as a flat set of slugs.
     * Delegates to the RBAC engine so inheritance is honoured.
     */
    public function permissionSlugs()
    {
        return app(RbacService::class)
            ->effectivePermissions($this);
    }

    /**
     * Permission check. When a $project is passed, project-level grants
     * (direct project roles, team assignments, team-scoped roles) are
     * merged with organization grants; otherwise only organization-wide
     * grants apply.
     */
    public function hasPermission(string $slug, ?Project $project = null): bool
    {
        return app(RbacService::class)->can($this, $slug, $project);
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id', 'office_id');
    }

    /**
     * Offices whose projects this user may browse beyond their own office
     * grants: their own office, plus any office their teams/projects already
     * connect them to (cross-office participation is honoured via the RBAC
     * engine's project-scoped sources, this is just a quick scoping aid).
     */
    public function officeIds(): Collection
    {
        $ids = collect();

        if ($this->office_id) {
            $ids->push((int) $this->office_id);
        }

        $viaTeams = Team::whereIn('team_id', $this->teamIds())
            ->whereNotNull('office_id')->pluck('office_id');

        return $ids->merge($viaTeams)->unique()->values();
    }

    /** True if the user heads this office. */
    public function headsOffice(Office $office): bool
    {
        return (int) $office->head_user_id === (int) $this->user_id;
    }

    /** True if the user heads any office at all. */
    public function headsAnyOffice(): bool
    {
        return Office::where('head_user_id', $this->user_id)->exists();
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    /** True if this account is still an unassigned public registrant.
     *  A user with any RBAC role attached is never a guest, even if the
     *  column was not updated by older code paths. */
    public function isGuest(): bool
    {
        return $this->role === 'guest'
            && $this->roles()->exists() === false;
    }

    /** True if the registration has not been approved/rejected yet. */
    public function isPending(): bool
    {
        return strtolower((string) $this->status) === 'pending';
    }

    public function isDirectorOrAdmin(): bool
    {
        return $this->hasPermission('edit_projects');
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('manage_users') || $this->hasPermission('manage_system_settings');
    }

    public function isProjectManager(): bool
    {
        return $this->hasPermission('create_projects');
    }

    public function isTeamLead(): bool
    {
        return $this->hasPermission('assign_tasks') && ! $this->hasPermission('manage_users');
    }

    public function isTeamMember(): bool
    {
        return ! $this->isTeamLead() && ! $this->isAdmin() && $this->hasPermission('update_task_status');
    }

    /** Org-scoped roles only (ignores project/team-scoped assignments). */
    public function organizationRoles()
    {
        return $this->roles->where('pivot.scope_type', null);
    }

    /** True if the user is allowed to spin up new projects — delegates to
     *  the create_projects permission rather than a hard-coded role check. */
    public function canCreateProjects(): bool
    {
        return $this->hasPermission('create_projects');
    }

    /** Team IDs this user belongs to — used to scope the dashboard for
     *  anyone who isn't a Director/Admin (who see everything). */
    public function teamIds()
    {
        return $this->teamMemberships()->pluck('team_id');
    }
}
