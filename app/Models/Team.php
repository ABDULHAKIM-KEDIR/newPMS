<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $primaryKey = 'team_id';
    public $timestamps = false;

    protected $fillable = ['team_name', 'team_leader_id', 'description'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'team_leader_id', 'user_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id', 'team_id');
    }
}
