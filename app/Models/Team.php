<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $primaryKey = 'team_id';

    public $timestamps = false;

    protected $fillable = ['team_name', 'team_leader_id', 'description', 'status', 'office_id', 'parent_team_id'];

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id', 'office_id');
    }

    public function parentTeam()
    {
        return $this->belongsTo(self::class, 'parent_team_id', 'team_id');
    }

    /**
     * dev's self-referencing subteams adapted to the standardized
     * 5-tier schema: sub-grouping lives in the sub_teams table.
     * (Single canonical relation; ->subteams and ->subTeams resolve
     * to this same method case-insensitively.)
     */
    public function subTeams()
    {
        return $this->hasMany(SubTeam::class, 'team_id', 'team_id');
    }

    public function childTeams()
    {
        return $this->hasMany(self::class, 'parent_team_id', 'team_id');
    }

    /**
     * All recursive child-team (parent_team_id) ids, used to prevent
     * circular parent-team assignment.
     *
     * @return Collection<int, int>
     */
    public function allDescendantIds(): Collection
    {
        $ids = collect();
        foreach ($this->childTeams as $sub) {
            $ids->push((int) $sub->team_id);
            $ids = $ids->merge($sub->allDescendantIds());
        }

        return $ids->unique();
    }

    public function wouldCauseCycle(int $newParentId): bool
    {
        if ($this->team_id && (int) $this->team_id === (int) $newParentId) {
            return true;
        }

        return $this->allDescendantIds()->contains((int) $newParentId);
    }

    public function heads()
    {
        return $this->morphMany(OrgUnitHead::class, 'headable');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'team_leader_id', 'user_id');
    }

    public static function countVisibleTo(User $user): int
    {
        return static::query()->visibleTo($user)->count();
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canAccessGlobalScope()) {
            return $query;
        }

        $officeIds = $user->officeScopeIds();

        if ($officeIds->isEmpty()) {
            return $query->whereNull('office_id');
        }

        $query->whereIn('office_id', $officeIds->all());

        if ($user->isTeamMember()) {
            $query->whereHas('members', fn ($memberQuery) => $memberQuery->where('team_members.user_id', $user->user_id));
        }

        return $query;
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
     * Resolve the project manager above this team: prefer the project whose
     * primary team is this one, then any project this team is assigned to.
     * Null when the team has no project or the project has no PM of record.
     */
    public function projectManager(): ?User
    {
        $project = Project::where('team_id', $this->team_id)
            ->orWhereHas('teams', fn ($q) => $q->where('teams.team_id', $this->team_id))
            ->orderByDesc('team_id')
            ->first();

        return $project?->projectManager;
    }

    public function officeHead(): ?User
    {
        return $this->office?->head;
    }

    public function departmentHead(): ?User
    {
        return $this->office?->department?->head;
    }

    /**
     * User ids holding leadership over this node and every parent node,
     * used by the hierarchical policies: Team Lead -> Project Manager ->
     * Office Head -> Department Head.
     *
     * @return array<int, int>
     */
    public function leadershipUserIds(): array
    {
        return collect([
            $this->team_leader_id,
            $this->projectManager()?->user_id,
            $this->office?->head_user_id,
            $this->office?->department?->head_user_id,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
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
