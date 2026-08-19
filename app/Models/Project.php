<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = 'project_id';
    public $timestamps = false;

    protected $fillable = [
        'project_name', 'description', 'project_type', 'team_id', 'template_id',
        'scope_statement', 'start_date', 'end_date', 'status', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id', 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function phases()
    {
        return $this->hasMany(Phase::class, 'project_id', 'project_id')->orderBy('sequence_order');
    }

    public function memberRoles()
    {
        return $this->hasMany(ProjectMemberRole::class, 'project_id', 'project_id');
    }

    public function deliverables()
    {
        return $this->hasMany(ProjectDeliverable::class, 'project_id', 'project_id');
    }

    public function changeRequests()
    {
        return $this->hasMany(ChangeRequest::class, 'project_id', 'project_id');
    }

    public function budget()
    {
        return $this->hasOne(ProjectBudget::class, 'project_id', 'project_id');
    }

    public function tasks()
    {
        return $this->hasManyThrough(Task::class, Phase::class, 'project_id', 'phase_id', 'project_id', 'phase_id');
    }

    // current phase = first phase not yet "Done"/"Closed", falls back to last phase
    public function currentPhaseIndex(): int
    {
        $phases = $this->phases()->pluck('status')->values();
        foreach ($phases as $i => $status) {
            if (!in_array($status, ['Done', 'Closed', 'Completed'])) {
                return $i;
            }
        }
        return max(0, $phases->count() - 1);
    }

    /**
     * True if the user can manage this project's tasks/assignments —
     * either they lead the project's team, or they hold a directorate-wide
     * management role (ICT Director / System Administrator).
     */
    /**
     * Blanket "manages this project" access is Director-scoped, not
     * Director-or-Admin — an Administrator only gets project authority if
     * a Director explicitly grants edit_projects/delete_projects to their
     * role from Roles & Access. This is checked alongside a permission gate
     * in the controller, not instead of one.
     */
    public function isManagedBy(User $user): bool
    {
        if ($user->hasRole('ICT Director')) {
            return true;
        }

        return optional($this->team)->team_leader_id === $user->user_id;
    }
}
