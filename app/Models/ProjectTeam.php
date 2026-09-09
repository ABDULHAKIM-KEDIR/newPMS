<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot for multi-team project collaboration. Each row is one team's
 * assignment to a project with a configurable access level ('view',
 * 'contribute', 'manage') and an optional project-scoped default role
 * applied to the team's members.
 */
class ProjectTeam extends Model
{
    public const ACCESS_LEVELS = ['view', 'contribute', 'manage'];

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $table = 'project_teams';

    protected $fillable = ['project_id', 'team_id', 'assigned_date', 'access_level', 'project_role_id'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function projectRole()
    {
        return $this->belongsTo(Role::class, 'project_role_id', 'role_id');
    }
}
