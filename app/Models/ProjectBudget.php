<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBudget extends Model
{
    protected $primaryKey = 'budget_id';
    public $timestamps = false;

    protected $fillable = ['project_id', 'allocated_amount', 'spent_amount', 'currency', 'updated_at'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function utilisationPercent(): int
    {
        if ((float) $this->allocated_amount <= 0) return 0;
        return (int) round(($this->spent_amount / $this->allocated_amount) * 100);
    }
}
