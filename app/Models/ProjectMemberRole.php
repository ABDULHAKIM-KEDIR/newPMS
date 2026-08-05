<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMemberRole extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_id', 'user_id', 'role_id', 'assigned_date'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }
}
