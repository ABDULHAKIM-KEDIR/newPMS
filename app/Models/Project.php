<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = 'project_id';

    public $timestamps = false;

    protected $fillable = [
        'project_name', 'description', 'client', 'project_type', 'team_id', 'template_id',
        'project_manager_id', 'scope_statement', 'start_date', 'end_date', 'priority', 'status', 'progress', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'project_teams', 'project_id', 'team_id')->withPivot('assigned_date');
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
     * Structured roster of all participants on this project with their roles and specialties.
     */
    public function getProjectRoster()
    {
        $this->loadMissing(['projectManager', 'team.leader', 'team.members.user', 'teams.leader', 'teams.members.user', 'memberRoles.user', 'memberRoles.role']);

        $roster = collect();
        $seen = [];
        $memberRoleMap = $this->memberRoles->keyBy('user_id');

        // 1. Project Manager
        if ($this->projectManager) {
            $pm = $this->projectManager;
            $roster->push((object) [
                'user_id' => $pm->user_id,
                'user' => $pm,
                'full_name' => $pm->full_name,
                'email' => $pm->email,
                'team_name' => 'Project Management',
                'project_role' => 'Project Manager',
                'specialty' => 'Project Management & Delivery',
                'badge_class' => 'b-active',
                'is_pm' => true,
                'is_leader' => false,
                'member_role_id' => null,
            ]);
            $seen[$pm->user_id] = true;
        }

        // 2. Team Leaders & Members from all assigned teams
        foreach ($this->allTeams() as $assignedTeam) {
            if ($assignedTeam->leader && ! isset($seen[$assignedTeam->leader->user_id])) {
                $tl = $assignedTeam->leader;
                $mr = $memberRoleMap->get($tl->user_id);
                $specialty = ($mr && $mr->specialty) ? $mr->specialty : "{$assignedTeam->team_name} Lead";
                $projectRole = ($mr && $mr->role) ? $mr->role->role_name : 'Team Lead';

                $roster->push((object) [
                    'user_id' => $tl->user_id,
                    'user' => $tl,
                    'full_name' => $tl->full_name,
                    'email' => $tl->email,
                    'team_name' => $assignedTeam->team_name,
                    'project_role' => $projectRole,
                    'specialty' => $specialty,
                    'badge_class' => 'b-planning',
                    'is_pm' => false,
                    'is_leader' => true,
                    'member_role_id' => $mr ? $mr->id : null,
                ]);
                $seen[$tl->user_id] = true;
            }

            foreach ($assignedTeam->members as $tm) {
                if ($tm->user && ! isset($seen[$tm->user_id])) {
                    $u = $tm->user;
                    $mr = $memberRoleMap->get($u->user_id);
                    $specialty = ($mr && $mr->specialty) ? $mr->specialty : (optional(optional($u)->roles->first())->role_name ?: 'Team Member');
                    $projectRole = ($mr && $mr->role) ? $mr->role->role_name : ($mr && $mr->specialty ? $mr->specialty : 'Team Member');

                    $roster->push((object) [
                        'user_id' => $u->user_id,
                        'user' => $u,
                        'full_name' => $u->full_name,
                        'email' => $u->email,
                        'team_name' => $assignedTeam->team_name,
                        'project_role' => $projectRole,
                        'specialty' => $specialty,
                        'badge_class' => 'b-risk',
                        'is_pm' => false,
                        'is_leader' => false,
                        'member_role_id' => $mr ? $mr->id : null,
                    ]);
                    $seen[$u->user_id] = true;
                }
            }
        }

        // 3. Project Member Roles (Specialists)
        foreach ($this->memberRoles as $mr) {
            if ($mr->user && ! isset($seen[$mr->user_id])) {
                $u = $mr->user;
                $specialtyName = $mr->specialty ?: (optional($mr->role)->role_name ?: 'Team Member');
                $roster->push((object) [
                    'user_id' => $u->user_id,
                    'user' => $u,
                    'full_name' => $u->full_name,
                    'email' => $u->email,
                    'team_name' => 'Specialist',
                    'project_role' => optional($mr->role)->role_name ?: 'Specialist',
                    'specialty' => $specialtyName,
                    'badge_class' => 'b-planning',
                    'is_pm' => false,
                    'is_leader' => false,
                    'member_role_id' => $mr->id,
                ]);
                $seen[$u->user_id] = true;
            }
        }

        return $roster;
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

        // Also allow assigning any other active user from the organization
        $otherUsers = User::where('status', 'Active')
            ->whereNotIn('user_id', $rosterUserIds)
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
     * Get all tasks associated with this project (via direct project_id or via phases)
     */
    public function allTasks()
    {
        $direct = $this->tasks()->with(['assignee', 'phase', 'team', 'comments', 'attachments'])->get();
        if ($direct->isNotEmpty()) {
            return $direct;
        }

        return $this->phaseTasks()->with(['assignee', 'phase', 'team', 'comments', 'attachments'])->get();
    }

    /**
     * Calculate dynamic progress percentage from completed tasks
     */
    public function progressPercentage(): int
    {
        $tasks = $this->allTasks();
        if ($tasks->isEmpty()) {
            return (int) ($this->progress ?: 0);
        }

        $done = $tasks->filter(fn ($t) => in_array($t->status, ['Done', 'Completed']))->count();

        return (int) round(($done / $tasks->count()) * 100);
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
     * Comprehensive task stats for dashboard cards
     */
    public function taskStats(): array
    {
        $tasks = $this->allTasks();
        $total = $tasks->count();
        $completed = $tasks->filter(fn ($t) => in_array($t->status, ['Done', 'Completed']))->count();
        $inProgress = $tasks->filter(fn ($t) => $t->status === 'In Progress')->count();
        $inReview = $tasks->filter(fn ($t) => $t->status === 'In Review')->count();
        $toDo = $tasks->filter(fn ($t) => in_array($t->status, ['Pending', 'To Do', 'Not started']))->count();
        $blocked = $tasks->filter(fn ($t) => $t->status === 'Blocked')->count();
        $overdue = $tasks->filter(fn ($t) => ! in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast())->count();

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
     * True if the user can manage this project's tasks/assignments —
     * either they lead the project's team, or they hold a directorate-wide
     * management role (ICT Director / System Administrator).
     */
    /**
     * Blanket "manages this project" access is Director-scoped, not
     * Director-or-Admin — an Administrator only gets project authority if
     * a Director explicitly grants edit_projects/delete_projects to their
     * role from Roles & Access. This is checked alongside a permission gate
     * in the controller, not instead of one.
     */
    public function isManagedBy(User $user): bool
    {
        if ($user->hasRole('ICT Director') || $user->hasRole('System Administrator') || $user->hasRole('Admin') || $user->hasRole('Project Manager')) {
            return true;
        }

        if ($this->project_manager_id && (int) $this->project_manager_id === (int) $user->user_id) {
            return true;
        }

        if (optional($this->team)->team_leader_id === $user->user_id) {
            return true;
        }

        if ($this->teams()->where('team_leader_id', $user->user_id)->exists()) {
            return true;
        }

        return false;
    }
}
