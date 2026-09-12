<?php

namespace App\Models;

use App\Services\RbacService;
use App\Services\RosterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    protected $primaryKey = 'project_id';

    public $timestamps = false;

    protected $fillable = [
        'project_name', 'description', 'client', 'project_type', 'project_type_id', 'team_id', 'template_id',
        'project_manager_id', 'scope_statement', 'start_date', 'end_date', 'priority', 'status', 'progress', 'created_by',
        'primary_office_id', 'department_id',
    ];

    /** Valid access levels for teams assigned to this project. */
    public const TEAM_ACCESS_LEVELS = ['view', 'contribute', 'manage'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id', 'project_type_id');
    }

    public function primaryOffice()
    {
        return $this->belongsTo(Office::class, 'primary_office_id', 'office_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function heads()
    {
        return $this->morphMany(OrgUnitHead::class, 'headable');
    }

    /** All participating offices, including the primary one. */
    public function offices()
    {
        return $this->belongsToMany(Office::class, 'project_office', 'project_id', 'office_id')
            ->withPivot('participation_type')
            ->withTimestamps();
    }

    /** Participating offices only (excludes the primary office). */
    public function participatingOffices()
    {
        return $this->belongsToMany(Office::class, 'project_office', 'project_id', 'office_id')
            ->wherePivot('participation_type', 'participating')
            ->withPivot('participation_type')
            ->withTimestamps();
    }

    /** True when the given office has a stake in this project (primary or participating). */
    public function involvesOffice(int $officeId): bool
    {
        if ((int) $this->primary_office_id === $officeId) {
            return true;
        }

        return $this->offices()->where('offices.office_id', $officeId)->exists();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'project_teams', 'project_id', 'team_id')
            ->withPivot('assigned_date', 'access_level', 'project_role_id');
    }

    /**
     * Pivots with access metadata, for the multi-team collaboration UI.
     */
    public function teamAssignments()
    {
        return $this->hasMany(ProjectTeam::class, 'project_id', 'project_id');
    }

    /**
     * Get all assigned teams including the primary team
     */
    public function allTeams()
    {
        $primary = $this->team ? collect([$this->team]) : collect();
        $assigned = $this->teams()->get();

        return $primary->merge($assigned)->unique('team_id');
    }

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id', 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id', 'user_id');
    }

    public function phases()
    {
        return $this->hasMany(Phase::class, 'project_id', 'project_id')->orderBy('sequence_order');
    }

    public function memberRoles()
    {
        return $this->hasMany(ProjectMemberRole::class, 'project_id', 'project_id');
    }

    /**
     * True when the user participates in this project in any capacity:
     * PM of record, member of the primary/assigned team, or a direct
     * project member role. This is the sole visibility criterion for
     * non-oversight roles.
     */
    public function participatesIn(User $user): bool
    {
        if ($this->project_manager_id && (int) $this->project_manager_id === (int) $user->user_id) {
            return true;
        }

        if ($this->relationLoaded('memberRoles')) {
            if ($this->memberRoles->contains('user_id', $user->user_id)) {
                return true;
            }
        } elseif ($this->memberRoles()->where('user_id', $user->user_id)->exists()) {
            return true;
        }

        return $this->allTeams()
            ->filter()
            ->contains(fn ($team) => $team->members->contains('user_id', $user->user_id));
    }

    /**
     * Query scope restricting projects to those under the user's office(s)
     * (primary or participating office) or those the user participates in
     * directly (PM of record, any assigned team they belong to, or direct
     * project membership). System administrators see everything.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasPermission('manage_system_settings')) {
            return $query;
        }

        $officeIds = $user->officeIds();

        return $query->where(function ($q) use ($user, $officeIds) {
            if ($officeIds->isNotEmpty()) {
                $q->orWhere(function ($oq) use ($officeIds) {
                    $oq->whereIn('primary_office_id', $officeIds)
                        ->orWhereHas('offices', fn ($oq2) => $oq2->whereIn('offices.office_id', $officeIds));
                });
            }

            $q->orWhere(function ($pq) use ($user) {
                $pq->where('project_manager_id', $user->user_id)
                    ->orWhereHas('memberRoles', fn ($mq) => $mq->where('project_member_roles.user_id', $user->user_id))
                    ->orWhereHas('team.members', fn ($tq) => $tq->where('team_members.user_id', $user->user_id))
                    ->orWhereHas('teams.members', fn ($tq) => $tq->where('team_members.user_id', $user->user_id));
            });
        });
    }

    /**
     * Everyone who can be assigned work on this project: the project manager,
     * the team leader, team members, and members assigned directly with roles/specialties.
     * Deduplicated by user id so a person holding multiple roles appears once.
     */
    public function members()
    {
        $users = collect();

        if ($this->project_manager_id && $this->projectManager) {
            $users->push($this->projectManager);
        }

        foreach ($this->allTeams() as $team) {
            if ($team->team_leader_id && $team->leader) {
                $users->push($team->leader);
            }

            $users = $users->merge($team->members->pluck('user')->filter());
        }

        if ($this->relationLoaded('memberRoles')) {
            $users = $users->merge($this->memberRoles->pluck('user')->filter());
        } else {
            $users = $users->merge($this->memberRoles()->with('user')->get()->pluck('user')->filter());
        }

        return $users->filter()->unique('user_id')->values();
    }

    /**
     * Structured roster of all participants on this project with their roles
     * and specialties. Presentation logic lives in RosterService; this thin
     * delegate keeps the historical model API for callers and tests.
     */
    public function getProjectRoster()
    {
        return app(RosterService::class)->getFormattedRoster($this);
    }

    /**
     * Users assignable for tasks with informative labels.
     */
    public function getAssignableUsersWithRoles()
    {
        $roster = $this->getProjectRoster();
        $rosterUserIds = $roster->pluck('user_id')->toArray();

        $list = $roster->map(function ($item) {
            $label = $item->full_name;
            if (! empty($item->project_role)) {
                $label .= " ({$item->project_role})";
            }

            return [
                'id' => $item->user_id,
                'name' => $label,
                'raw_name' => $item->full_name,
                'role' => $item->project_role,
                'specialty' => $item->specialty,
                'is_project_team' => true,
            ];
        });

        // Also allow assigning other active users, restricted to the
        // project's primary and participating offices.
        $allowedOfficeIds = $this->authorizedOfficeIds();

        $otherUsers = User::where('status', 'Active')
            ->whereNotIn('user_id', $rosterUserIds)
            ->when($allowedOfficeIds->isNotEmpty(), fn ($q) => $q->where(function ($q2) use ($allowedOfficeIds) {
                $q2->whereNull('office_id')->orWhereIn('office_id', $allowedOfficeIds->all());
            }))
            ->orderBy('full_name')
            ->get();

        foreach ($otherUsers as $ou) {
            $list->push([
                'id' => $ou->user_id,
                'name' => $ou->full_name,
                'raw_name' => $ou->full_name,
                'role' => optional(optional($ou)->roles->first())->role_name ?: 'Team Member',
                'specialty' => null,
                'is_project_team' => false,
            ]);
        }

        return $list->values();
    }

    /**
     * Offices this project may draw members/users from: primary + participating.
     *
     * @return Collection<int, int>
     */
    public function authorizedOfficeIds(): Collection
    {
        return collect([$this->primary_office_id])
            ->merge($this->offices()->pluck('offices.office_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * True when a user may be assigned to this project. Users with an office
     * must belong to one of the project's offices; legacy users without an
     * office (and projects without offices) keep working as before.
     */
    public function canAssignUser(User $user): bool
    {
        $allowedOfficeIds = $this->authorizedOfficeIds();

        if ($allowedOfficeIds->isEmpty() || ! $user->office_id) {
            return true;
        }

        return $allowedOfficeIds->contains((int) $user->office_id);
    }

    public function deliverables()
    {
        return $this->hasMany(ProjectDeliverable::class, 'project_id', 'project_id');
    }

    public function changeRequests()
    {
        return $this->hasMany(ChangeRequest::class, 'project_id', 'project_id');
    }

    public function budget()
    {
        return $this->hasOne(ProjectBudget::class, 'project_id', 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id', 'project_id');
    }

    public function phaseTasks()
    {
        return $this->hasManyThrough(Task::class, Phase::class, 'project_id', 'phase_id', 'project_id', 'phase_id');
    }

    /**
     * Get all tasks associated with this project (via direct project_id or via phases).
     * Reuses an already-eager-loaded `tasks` relation to avoid re-querying.
     */
    public function allTasks()
    {
        if ($this->relationLoaded('tasks') && $this->tasks->isNotEmpty()) {
            return $this->tasks;
        }

        $direct = $this->tasks()->with(['assignee', 'phase', 'team', 'comments', 'attachments'])->get();
        if ($direct->isNotEmpty()) {
            return $direct;
        }

        return $this->phaseTasks()->with(['assignee', 'phase', 'team', 'comments', 'attachments'])->get();
    }

    /**
     * Calculate dynamic progress percentage from completed tasks.
     * Uses a SQL aggregate instead of loading task models into memory.
     */
    public function progressPercentage(): int
    {
        $stats = $this->taskCountByStatus();
        $total = array_sum($stats);
        if ($total === 0) {
            return (int) ($this->progress ?: 0);
        }

        $done = ($stats['Done'] ?? 0) + ($stats['Completed'] ?? 0);

        return (int) round(($done / $total) * 100);
    }

    /**
     * Recalculate and persist progress to the database
     */
    public function recalculateProgress(): int
    {
        $calc = $this->progressPercentage();
        $this->update(['progress' => $calc]);

        return $calc;
    }

    /**
     * Task counts grouped by status in a single SQL aggregate query,
     * covering tasks linked directly or through phases. Returns an
     * associative array of [status => count].
     *
     * @return array<string, int>
     */
    public function taskCountByStatus(): array
    {
        $rows = DB::table('tasks')
            ->leftJoin('phases', 'phases.phase_id', '=', 'tasks.phase_id')
            ->where(function ($q) {
                $q->where('tasks.project_id', $this->project_id)
                    ->orWhere('phases.project_id', $this->project_id);
            })
            ->groupBy('tasks.status')
            ->select('tasks.status', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'status')
            ->all();

        return array_map('intval', $rows);
    }

    /**
     * Comprehensive task stats for dashboard cards, aggregated in SQL
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

        $overdue = (int) DB::table('tasks')
            ->leftJoin('phases', 'phases.phase_id', '=', 'tasks.phase_id')
            ->where(function ($q) {
                $q->where('tasks.project_id', $this->project_id)
                    ->orWhere('phases.project_id', $this->project_id);
            })
            ->whereNotIn('tasks.status', ['Done', 'Completed'])
            ->whereNotNull('tasks.end_date')
            ->where('tasks.end_date', '<', now()->toDateString())
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'in_review' => $inReview,
            'to_do' => $toDo,
            'blocked' => $blocked,
            'overdue' => $overdue,
            'progress' => $total > 0 ? (int) round(($completed / $total) * 100) : (int) ($this->progress ?: 0),
        ];
    }

    // current phase = first phase not yet "Done"/"Closed", falls back to last phase
    public function currentPhaseIndex(): int
    {
        $phases = $this->phases()->pluck('status')->values();
        foreach ($phases as $i => $status) {
            if (! in_array($status, ['Done', 'Closed', 'Completed'])) {
                return $i;
            }
        }

        return max(0, $phases->count() - 1);
    }

    /**
     * Blanket "manages this project" authority is resolved through the
     * RBAC engine (org roles, the PM of record, team leaders with a
     * manage-level team assignment, or explicit project roles) rather
     * than hard-coded role names.
     */
    public function isManagedBy(User $user): bool
    {
        if ($user->hasPermission('edit_projects')) {
            return true;
        }

        if ($this->project_manager_id && (int) $this->project_manager_id === (int) $user->user_id) {
            return true;
        }

        $rbac = app(RbacService::class);

        // A team leader whose team is assigned with manage access manages
        // the project; a direct project role granting edit_projects too.
        return $rbac->can($user, 'edit_projects', $this);
    }
}
