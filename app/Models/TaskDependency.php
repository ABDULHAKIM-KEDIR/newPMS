<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskDependency extends Model
{
    protected $primaryKey = 'dependency_id';
    public $timestamps = false;

    protected $fillable = ['task_id', 'depends_on_task_id', 'dependency_type'];
}
