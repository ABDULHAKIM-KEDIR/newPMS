<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $primaryKey = 'team_member_id';
    public $timestamps = false;

    protected $fillable = ['team_id', 'user_id', 'joined_date'];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
