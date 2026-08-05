<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $primaryKey = 'task_id';
    public $timestamps = false;

    protected $fillable = [
        'phase_id', 'parent_task_id', 'task_name', 'description', 'assigned_to',
        'status', 'priority', 'start_date', 'end_date', 'duration',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id', 'task_id')->orderBy('created_at');
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
