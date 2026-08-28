<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectType extends Model
{
    protected $primaryKey = 'project_type_id';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function projects()
    {
        return $this->hasMany(Project::class, 'project_type_id', 'project_type_id');
    }
}
