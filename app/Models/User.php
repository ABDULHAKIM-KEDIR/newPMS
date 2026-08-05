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
}
