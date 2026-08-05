<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskProgressLog extends Model
{
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = ['task_id', 'user_id', 'previous_status', 'new_status', 'remarks'];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
