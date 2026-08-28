<?php

namespace App\Models;

use App\Services\RbacService;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = ['full_name', 'email', 'password_hash', 'phone', 'department', 'avatar', 'status', 'role'];

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
