<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = ['full_name', 'email', 'password_hash', 'phone', 'status'];

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
        return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }

    /**
     * True if the user carries the given directorate-wide role
     * (e.g. 'ICT Director', 'System Administrator'). Roles are loaded
     * eagerly where possible to avoid N+1 queries.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('role_name', $roleName);
    }

    /** Every permission this user's role(s) grant, as a flat set of slugs. */
    public function permissionSlugs()
    {
        return $this->roles->flatMap->permissions->pluck('permission_name')->unique();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->permissionSlugs()->contains($slug);
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function isDirectorOrAdmin(): bool
    {
        return $this->hasRole('ICT Director') || $this->hasRole('System Administrator');
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
