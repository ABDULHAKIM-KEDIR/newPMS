<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgUnitHead extends Model
{
    protected $table = 'org_unit_heads';

    protected $fillable = ['headable_type', 'headable_id', 'user_id'];

    public function headable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
