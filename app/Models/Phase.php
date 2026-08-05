<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    protected $primaryKey = 'phase_id';
    public $timestamps = false;

    protected $fillable = [
        'project_id', 'phase_name', 'start_date', 'end_date', 'duration', 'status', 'sequence_order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'phase_id', 'phase_id');
    }

    public function budget()
    {
        return $this->hasOne(PhaseBudget::class, 'phase_id', 'phase_id');
    }
}
