<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $primaryKey = 'team_id';

    public $timestamps = false;

    protected $fillable = ['team_name', 'team_leader_id', 'description', 'status'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'team_leader_id', 'user_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')->withPivot('joined_date');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id', 'team_id');
    }

    public function assignedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_teams', 'team_id', 'project_id')->withPivot('assigned_date');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'team_id', 'team_id');
    }

    /**
     * All projects this team is involved in (primary or assigned via project_teams)
     */
    public function allProjects()
    {
        $primary = $this->projects()->get();
        $assigned = $this->assignedProjects()->get();

        return $primary->merge($assigned)->unique('project_id');
    }

    /**
     * Calculate team progress percentage across all its tasks.
     * Uses a SQL aggregate instead of loading task models into memory.
     */
    public function progressPercentage(): int
    {
        $stats = $this->taskCountByStatus();
        $total = array_sum($stats);
        if ($total === 0) {
            return 0;
        }

        $done = ($stats['Done'] ?? 0) + ($stats['Completed'] ?? 0);

        return (int) round(($done / $total) * 100);
    }

    /**
     * Task counts grouped by status in a single SQL aggregate query.
     *
     * @return array<string, int>
     */
    public function taskCountByStatus(): array
    {
        return array_map('intval', $this->tasks()
            ->groupBy('status')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'status')
            ->all());
    }

    /**
     * Get task statistics breakdown for this team, aggregated in SQL
     * instead of filtering a loaded collection in PHP.
     */
    public function taskStats(): array
    {
        $byStatus = $this->taskCountByStatus();
        $total = array_sum($byStatus);
        $completed = ($byStatus['Done'] ?? 0) + ($byStatus['Completed'] ?? 0);
        $inProgress = $byStatus['In Progress'] ?? 0;
        $inReview = $byStatus['In Review'] ?? 0;
        $toDo = ($byStatus['Pending'] ?? 0) + ($byStatus['To Do'] ?? 0) + ($byStatus['Not started'] ?? 0);
        $blocked = $byStatus['Blocked'] ?? 0;

        $overdue = (int) $this->tasks()
            ->whereNotIn('status', ['Done', 'Completed'])
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString())
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'in_review' => $inReview,
            'to_do' => $toDo,
            'blocked' => $blocked,
            'overdue' => $overdue,
            'progress' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }
}
