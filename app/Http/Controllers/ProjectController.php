<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\ChangeRequest;
use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectDeliverable;
use App\Models\ProjectMemberRole;
use App\Models\ProjectType;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\ProjectWizardService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(private ProjectWizardService $projectWizardService) {}

    private const PHASES = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];

    private const TYPES = ['Software', 'Network & Infrastructure', 'Training & Consultancy', 'Enterprise Systems', 'Research & Development'];

    private const STATUSES = ['planning', 'active', 'risk', 'closed'];

    private const PRIORITIES = ['Low', 'Medium', 'High', 'Urgent'];

    public const SPECIALTIES = [
        'UI/UX Designer',
        'Frontend Developer',
        'Backend Developer',
        'Full Stack Developer',
        'Mobile App Developer',
        'QA / Test Engineer',
        'DevOps Engineer',
        'Database Administrator',
        'System Analyst',
        'Security Specialist',
        'Technical Writer',
    ];

    public function index(Request $request)
    {
        Gate::authorize('view_projects');

        $query = Project::with(['team.leader', 'teams.leader', 'projectManager', 'budget', 'tasks', 'phases.tasks', 'memberRoles.user']);

        if ($type = $request->get('type')) {
            $query->where('project_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderByDesc('project_id')->paginate(15)->withQueryString();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'team.leader',
            'team.members.user',
            'teams.leader',
            'teams.members.user',
            'projectManager',
            'budget',
            'phases.budget',
            'phases.tasks.assignee',
            'phases.tasks.team',
            'phases.tasks.phase',
            'deliverables',
            'changeRequests.requester',
            'memberRoles.user',
            'memberRoles.role',
        ]);

        $tasks = $project->allTasks();
        $assignableUsers = $project->getAssignableUsersWithRoles();
        $projectRoster = $project->getProjectRoster();
        $taskStats = $project->taskStats();
        $allTeams = Team::where('status', 'Active')->with('leader')->orderBy('team_name')->get();

        return view('projects.show', compact('project', 'tasks', 'assignableUsers', 'projectRoster', 'taskStats', 'allTeams'));
    }

    public function create()
    {
        Gate::authorize('create_projects');

        $teams = Team::with(['leader', 'members.user'])->orderBy('team_name')->get();
        $projectManagers = User::where('status', 'Active')->orderBy('full_name')->get();
        $projectTypes = ProjectType::where('is_active', true)->orderBy('name')->get();

        $teamsData = $teams->map(function ($t) {
            return [
                'id' => $t->team_id,
                'name' => $t->team_name,
                'leader_name' => optional($t->leader)->full_name ?? 'Unassigned',
                'members' => $t->members->map(function ($m) {
                    return [
                        'id' => $m->user ? $m->user->user_id : null,
                        'name' => $m->user ? $m->user->full_name : 'Member',
                    ];
                })->filter(fn ($m) => ! is_null($m['id']))->values()->all(),
            ];
        })->values()->all();

        return view('projects.create', [
            'teams' => $teams,
            'teamsData' => $teamsData,
            'types' => self::TYPES,
            'projectTypes' => $projectTypes,
            'priorities' => self::PRIORITIES,
            'projectManagers' => $projectManagers,
        ]);
    }

    public function saveWizardStep(Request $request)
    {
        Gate::authorize('create_projects');

        /** @var User $user */
        $user = Auth::user();

        $step = (int) $request->input('step');
        $projectId = $request->input('project_id');

        if ($step === 1) {
            $data = $request->validate([
                'project_name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:2000'],
                'client' => ['nullable', 'string', 'max:150'],
                'project_type' => ['nullable', 'string', 'max:100'],
                'project_type_id' => ['nullable', 'exists:project_types,project_type_id'],
                'project_manager_id' => ['nullable', 'exists:users,user_id'],
                'priority' => ['nullable', 'in:Low,Medium,High,Urgent'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'allocated_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            $project = $projectId ? Project::findOrFail($projectId) : new Project;
            $project->fill([
                'project_name' => $data['project_name'],
                'description' => $data['description'] ?? null,
                'client' => $data['client'] ?? null,
                'project_type' => $data['project_type'] ?? optional(ProjectType::find($data['project_type_id'] ?? null))->name ?? 'Software',
                'project_type_id' => $this->resolveProjectTypeId($data),
                'project_manager_id' => $data['project_manager_id'] ?? null,
                'priority' => $data['priority'] ?? 'Medium',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => 'planning',
                'progress' => 0,
                'created_by' => $project->created_by ?: $user->user_id,
            ]);
            $project->save();

            ProjectBudget::updateOrCreate(['project_id' => $project->project_id], [
                'allocated_amount' => $data['allocated_amount'] ?? 0,
                'spent_amount' => 0,
                'currency' => 'ETB',
            ]);

            if ($project->phases()->count() === 0) {
                foreach (self::PHASES as $i => $phaseName) {
                    $phase = Phase::create([
                        'project_id' => $project->project_id,
                        'phase_name' => $phaseName,
                        'status' => $i === 0 ? 'In Progress' : 'Not started',
                        'sequence_order' => $i,
                    ]);
                    PhaseBudget::create([
                        'phase_id' => $phase->phase_id,
                        'allocated_amount' => round(($data['allocated_amount'] ?? 0) / 5),
                        'spent_amount' => 0,
                    ]);
                }
            }
        } elseif ($step === 2) {
            $project = Project::findOrFail($projectId);
            $data = $request->validate([
                'teams' => ['required', 'array', 'min:1'],
                'teams.*' => ['exists:teams,team_id'],
            ]);
            $teamIds = collect($data['teams'])->map(fn ($id) => (int) $id)->unique()->values();
            $project->update(['team_id' => $teamIds->first()]);
            DB::table('project_teams')->where('project_id', $project->project_id)->delete();
            foreach ($teamIds as $teamId) {
                DB::table('project_teams')->insert([
                    'project_id' => $project->project_id,
                    'team_id' => $teamId,
                    'assigned_date' => now(),
                ]);
            }
        } elseif ($step === 3) {
            $project = Project::findOrFail($projectId);
            $data = $request->validate([
                'tasks' => ['nullable', 'array'],
                'tasks.*.task_name' => ['nullable', 'string', 'max:150'],
                'tasks.*.team_id' => ['nullable', 'exists:teams,team_id'],
                'tasks.*.assigned_to' => ['nullable'],
                'tasks.*.priority' => ['nullable', 'in:Low,Medium,High,Urgent'],
                'tasks.*.budget' => ['nullable', 'numeric', 'min:0'],
                'tasks.*.start_date' => ['nullable', 'date'],
                'tasks.*.end_date' => ['nullable', 'date', 'after_or_equal:tasks.*.start_date'],
            ]);
            $firstPhase = $project->phases()->orderBy('sequence_order')->first();
            $project->tasks()->delete();
            foreach ($data['tasks'] ?? [] as $taskData) {
                if (blank($taskData['task_name'] ?? null)) {
                    continue;
                }

                $assigneeId = $this->resolveUserId($taskData['assigned_to'] ?? null, $taskData['team_id'] ?? $project->team_id);
                Task::create([
                    'project_id' => $project->project_id,
                    'phase_id' => $firstPhase?->phase_id,
                    'team_id' => $taskData['team_id'] ?? $project->team_id,
                    'task_name' => $taskData['task_name'],
                    'assigned_to' => $assigneeId,
                    'priority' => $taskData['priority'] ?? 'Medium',
                    'status' => 'To Do',
                    'budget' => $taskData['budget'] ?? 0,
                    'start_date' => $taskData['start_date'] ?? null,
                    'end_date' => $taskData['end_date'] ?? null,
                    'progress' => 0,
                ]);
            }
            $project->recalculateProgress();
        } elseif ($step === 4) {
            $project = Project::findOrFail($projectId);

            return response()->json(['redirect' => route('projects.show', $project)]);
        } else {
            abort(422, 'Invalid wizard step.');
        }

        return response()->json([
            'project_id' => $project->project_id,
            'message' => "Step {$step} saved successfully.",
        ]);
    }

    /**
     * Prefers an explicit project_type_id; otherwise falls back to the
     * legacy free-text project_type string so old clients keep working.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveProjectTypeId(array $data): ?int
    {
        if (! empty($data['project_type_id'])) {
            return (int) $data['project_type_id'];
        }

        $legacy = trim((string) ($data['project_type'] ?? ''));

        if ($legacy === '') {
            return null;
        }

        return ProjectType::whereRaw('lower(name) = ?', [strtolower($legacy)])
            ->value('project_type_id');
    }

    public function store(StoreProjectRequest $request)
    {
        Gate::authorize('create_projects');

        $project = $this->projectWizardService->handleWizardSave($request);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        /** @var User $user */
        $user = Auth::user();

        return view('projects.edit', [
            'project' => $project->load(['budget', 'memberRoles.user', 'memberRoles.role', 'projectManager', 'team.leader']),
            'teams' => $this->eligibleTeamsFor($user, $project),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'projectManagers' => User::where('status', 'Active')->orderBy('full_name')->get(),
            'canEditBudget' => $user->can('manage_budgets'),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);
        /** @var User $user */
        $user = Auth::user();

        $pmInput = $request->input('project_manager_id') ?? $request->input('project_manager_name');
        $resolvedPmId = $this->projectWizardService->resolveUserId($pmInput, $request->input('team_id') ?? $project->team_id);

        $data = $request->validated();

        $project->update([
            'project_name' => $data['project_name'],
            'description' => $data['description'] ?? null,
            'project_type' => $data['project_type'],
            'team_id' => $data['team_id'],
            'project_manager_id' => $resolvedPmId,
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        // Safe Member Synchronization
        if ($request->has('members') && is_array($request->input('members'))) {
            $submittedUserIds = [];

            foreach ($request->input('members') as $memberData) {
                if (! empty($memberData['user_id'])) {
                    $submittedUserIds[] = $memberData['user_id'];

                    ProjectMemberRole::updateOrCreate(
                        [
                            'project_id' => $project->project_id,
                            'user_id' => $memberData['user_id'],
                        ],
                        [
                            'role_id' => $memberData['role_id'] ?? null,
                            'specialty' => $memberData['specialty'] ?? null,
                            'assigned_date' => now()->toDateString(),
                        ]
                    );
                }
            }

            // Only remove members that were explicitly removed from the form list
            if (! empty($submittedUserIds)) {
                $project->memberRoles()->whereNotIn('user_id', $submittedUserIds)->delete();
            }
        }

        // Budget figures sync
        if (isset($data['allocated_amount']) && $project->budget && $user->can('manage_budgets')) {
            $previous = $project->budget->allocated_amount;
            $project->budget->update(['allocated_amount' => $data['allocated_amount']]);
            if ((float) $previous !== (float) $data['allocated_amount']) {
                Activity::log('Updated project budget', 'Project', $project->project_id, "{$project->project_name}: ETB ".number_format($previous).' → ETB '.number_format($data['allocated_amount']));
            }
        }

        Activity::log('Updated project', 'Project', $project->project_id, $project->project_name);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated successfully.');
    }

    public function updateSchedule(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project->update([
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        Activity::log('Updated project schedule', 'Project', $project->project_id, $project->project_name);

        return back()->with('status', 'Project schedule updated successfully.');
    }

    public function assignTeam(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,team_id'],
        ]);

        $team = Team::findOrFail($data['team_id']);

        DB::table('project_teams')->insertOrIgnore([
            'project_id' => $project->project_id,
            'team_id' => $team->team_id,
            'assigned_date' => now(),
        ]);

        Activity::log('Assigned team to project', 'Project', $project->project_id, "{$team->team_name} → {$project->project_name}");

        if ($team->team_leader_id && (int) $team->team_leader_id !== (int) $user->user_id) {
            Activity::notify($team->team_leader_id, "Your team ({$team->team_name}) was assigned to project: \"{$project->project_name}\"", 'project');
        }

        return back()->with('status', "Team \"{$team->team_name}\" assigned to project successfully.");
    }

    public function removeTeam(Project $project, Team $team)
    {
        $this->authorize('update', $project);

        DB::table('project_teams')
            ->where('project_id', $project->project_id)
            ->where('team_id', $team->team_id)
            ->delete();

        Activity::log('Removed team from project', 'Project', $project->project_id, "{$team->team_name} removed from {$project->project_name}");

        return back()->with('status', "Team \"{$team->team_name}\" unassigned from this project.");
    }

    public function addMember(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        /** @var User $user */
        $user = Auth::user();

        $input = $request->input('user_id') ?? $request->input('user_name') ?? $request->input('name');
        $resolvedUserId = $this->projectWizardService->resolveUserId($input, $project->team_id);

        if (! $resolvedUserId) {
            return back()->withErrors(['user_id' => 'Please provide a valid member name or select from the list.']);
        }

        $specialty = $request->input('specialty');
        $roleId = $request->input('role_id');

        $existing = $project->memberRoles()->where('user_id', $resolvedUserId)->first();
        if ($existing) {
            $existing->update([
                'role_id' => $roleId ?? $existing->role_id,
                'specialty' => $specialty ?? $existing->specialty,
            ]);
        } else {
            ProjectMemberRole::create([
                'project_id' => $project->project_id,
                'user_id' => $resolvedUserId,
                'role_id' => $roleId ?? null,
                'specialty' => $specialty ?? null,
                'assigned_date' => now()->toDateString(),
            ]);
        }

        $assignedUser = User::find($resolvedUserId);
        $roleLabel = $specialty ?: 'Team Member';
        Activity::log('Added project member', 'Project', $project->project_id, "{$assignedUser->full_name} ({$roleLabel}) → {$project->project_name}");

        if ((int) $resolvedUserId !== (int) $user->user_id) {
            Activity::notify((int) $resolvedUserId, "You have been added to project \"{$project->project_name}\" as {$roleLabel}", 'project');
        }

        return back()->with('status', "{$assignedUser->full_name} assigned as {$roleLabel}.");
    }

    public function storeDeliverable(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'deliverable_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:Pending,Delivered'],
        ]);

        $deliverable = ProjectDeliverable::create([
            'project_id' => $project->project_id,
            'deliverable_name' => $data['deliverable_name'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'Pending',
        ]);

        Activity::log('Added project deliverable', 'Project', $project->project_id, "{$deliverable->deliverable_name} on {$project->project_name}");

        return back()->with('status', "Deliverable \"{$deliverable->deliverable_name}\" added successfully.");
    }

    public function toggleDeliverable(Project $project, ProjectDeliverable $deliverable)
    {
        $this->authorize('update', $project);
        abort_unless((int) $deliverable->project_id === (int) $project->project_id, 404);

        $newStatus = $deliverable->status === 'Delivered' ? 'Pending' : 'Delivered';
        $deliverable->update(['status' => $newStatus]);

        Activity::log('Updated deliverable status', 'Project', $project->project_id, "{$deliverable->deliverable_name} → {$newStatus}");

        return back()->with('status', "Deliverable marked as {$newStatus}.");
    }

    public function destroyDeliverable(Project $project, ProjectDeliverable $deliverable)
    {
        $this->authorize('update', $project);
        abort_unless((int) $deliverable->project_id === (int) $project->project_id, 404);

        $name = $deliverable->deliverable_name;
        $deliverable->delete();

        Activity::log('Deleted project deliverable', 'Project', $project->project_id, "{$name} removed from {$project->project_name}");

        return back()->with('status', "Deliverable \"{$name}\" removed.");
    }

    public function updateMember(Request $request, Project $project, ProjectMemberRole $memberRole)
    {
        $this->authorize('update', $project);
        abort_unless((int) $memberRole->project_id === (int) $project->project_id, 404);

        $data = $request->validate([
            'specialty' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'exists:roles,role_id'],
        ]);

        $memberRole->update([
            'specialty' => $data['specialty'] ?? null,
            'role_id' => $data['role_id'] ?? null,
        ]);

        $userName = optional($memberRole->user)->full_name ?? 'Member';
        $specialty = $data['specialty'] ?: 'Member';
        Activity::log('Updated project member role', 'Project', $project->project_id, "{$userName} role updated to {$specialty}");

        return back()->with('status', "Role updated for {$userName}.");
    }

    public function removeMember(Project $project, ProjectMemberRole $memberRole)
    {
        $this->authorize('update', $project);
        abort_unless((int) $memberRole->project_id === (int) $project->project_id, 404);

        $userName = optional($memberRole->user)->full_name ?? 'A member';
        $memberRole->delete();

        Activity::log('Removed project member', 'Project', $project->project_id, "{$userName} removed from {$project->project_name}");

        return back()->with('status', "{$userName} was removed from this project roster.");
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $name = $project->project_name;
        Activity::log('Deleted project', 'Project', $project->project_id, $name);
        $project->delete();

        return redirect()->route('projects.index')->with('status', "\"{$name}\" was deleted.");
    }

    public function storeChangeRequest(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $data = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $cr = ChangeRequest::create([
            'project_id' => $project->project_id,
            'requested_by' => Auth::id(),
            'description' => $data['description'],
            'status' => 'Pending',
            'requested_date' => now(),
        ]);

        Activity::log('Created change request', 'ChangeRequest', $cr->change_request_id, $data['description']);

        if (optional($project->team)->team_leader_id) {
            Activity::notify($project->team->team_leader_id, Auth::user()->full_name." filed a change request on \"{$project->project_name}\"", 'approval');
        }

        return back()->with('status', 'Change request submitted.');
    }

    private function resolveUserId(string|int|null $input, ?int $teamId = null): ?int
    {
        return $this->projectWizardService->resolveUserId($input, $teamId);
    }

    /**
     * Teams this user is allowed to create/assign a project under: any team
     * for a Director/Admin (or anyone editing a project they already manage),
     * otherwise only teams they actually lead.
     */
    private function eligibleTeamsFor(User $user, ?Project $editingProject = null): Collection
    {
        if ($user->isDirectorOrAdmin()) {
            return Team::orderBy('team_name')->get();
        }

        $led = Team::where('team_leader_id', $user->user_id)->orderBy('team_name')->get();

        if ($editingProject && $editingProject->isManagedBy($user) && ! $led->contains('team_id', $editingProject->team_id)) {
            $led->push($editingProject->team);
        }

        return $led;
    }
}
