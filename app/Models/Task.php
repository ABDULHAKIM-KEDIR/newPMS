<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $primaryKey = 'task_id';

    public $timestamps = false;

    protected $fillable = [
        'project_id', 'phase_id', 'team_id', 'parent_task_id', 'task_name', 'description', 'assigned_to',
        'status', 'priority', 'progress', 'budget', 'blocker_reason', 'start_date', 'end_date', 'duration',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id', 'phase_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id', 'task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id', 'task_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'entity_id', 'task_id')->where('entity_type', 'Task')->orderByDesc('uploaded_at');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id', 'task_id')->orderBy('created_at');
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['Done', 'Completed']) && $this->end_date && $this->end_date->isPast();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'Done', 'Completed' => 'b-active',
            'In Progress' => 'b-planning',
            'In Review' => 'b-review',
            'Blocked' => 'b-blocked',
            default => 'b-risk',
        };
    }

    public function priorityBadgeClass(): string
    {
        return 'p-'.strtolower($this->priority ?: 'medium');
    }

    public function progressLogs()
    {
        return $this->hasMany(TaskProgressLog::class, 'task_id', 'task_id')->orderByDesc('changed_at');
    }

    // tasks this one depends on
    public function dependencies()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withPivot('dependency_type');
    }

    // tasks that depend on this one
    public function dependents()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withPivot('dependency_type');
    }
}
