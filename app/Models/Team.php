<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $primaryKey = 'team_id';

    public $timestamps = false;

    protected $fillable = ['team_name', 'team_leader_id', 'description', 'status'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'team_leader_id', 'user_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')->withPivot('joined_date');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id', 'team_id');
    }

    public function assignedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_teams', 'team_id', 'project_id')->withPivot('assigned_date');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'team_id', 'team_id');
    }

    /**
     * All projects this team is involved in (primary or assigned via project_teams)
     */
    public function allProjects()
    {
        $primary = $this->projects()->get();
        $assigned = $this->assignedProjects()->get();

        return $primary->merge($assigned)->unique('project_id');
    }

    /**
     * Calculate team progress percentage across all its tasks
     */
    public function progressPercentage(): int
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) {
            return 0;
        }

        $done = $tasks->filter(fn ($t) => in_array($t->status, ['Done', 'Completed']))->count();

        return (int) round(($done / $tasks->count()) * 100);
    }

    /**
     * Get task statistics breakdown for this team
     */
    public function taskStats(): array
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        $completed = $tasks->filter(fn ($t) => in_array($t->status, ['Done', 'Completed']))->count();
        $inProgress = $tasks->filter(fn ($t) => $t->status === 'In Progress')->count();
        $inReview = $tasks->filter(fn ($t) => $t->status === 'In Review')->count();
        $toDo = $tasks->filter(fn ($t) => in_array($t->status, ['Pending', 'To Do', 'Not started']))->count();
        $blocked = $tasks->filter(fn ($t) => $t->status === 'Blocked')->count();
        $overdue = $tasks->filter(fn ($t) => ! in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast())->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'in_review' => $inReview,
            'to_do' => $toDo,
            'blocked' => $blocked,
            'overdue' => $overdue,
            'progress' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }
}
