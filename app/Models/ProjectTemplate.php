<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $primaryKey = 'template_id';
    public $timestamps = false;

    protected $fillable = ['template_name', 'project_type', 'description'];

    public function projects()
    {
        return $this->hasMany(Project::class, 'template_id', 'template_id');
    }
}
