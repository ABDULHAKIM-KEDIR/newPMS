<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDeliverable extends Model
{
    protected $primaryKey = 'deliverable_id';
    public $timestamps = false;

    protected $fillable = ['project_id', 'deliverable_name', 'description', 'due_date', 'status'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
