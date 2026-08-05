<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $primaryKey = 'comment_id';
    public $timestamps = false;

    protected $fillable = ['task_id', 'user_id', 'comment_text'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
