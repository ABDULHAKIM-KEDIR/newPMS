<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhaseBudget extends Model
{
    protected $primaryKey = 'budget_id';
    public $timestamps = false;

    protected $fillable = ['phase_id', 'allocated_amount', 'spent_amount', 'updated_at'];

    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id', 'phase_id');
    }
}
