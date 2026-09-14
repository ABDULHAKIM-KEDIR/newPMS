<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An optional refinement of a Team. A sub-team always belongs to exactly
 * one team; a task or membership may omit it and simply sit at the
 * team level.
 */
class SubTeam extends Model
{
    protected $primaryKey = 'sub_team_id';

    public $timestamps = false;

    protected $fillable = ['team_id', 'sub_team_name', 'lead_user_id', 'description', 'status'];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_user_id', 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'sub_team_id', 'sub_team_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'sub_team_id', 'sub_team_id');
    }

    /**
     * User ids holding leadership over this node and every parent node,
     * used by the hierarchical policies: Sub-Team Lead -> Team Lead ->
     * Project Manager -> Office Head -> Department Head.
     *
     * @return array<int, int>
     */
    public function leadershipUserIds(): array
    {
        return collect([$this->lead_user_id, $this->team?->leadershipUserIds()])
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
