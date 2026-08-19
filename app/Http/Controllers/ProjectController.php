<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Team;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const PHASES = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
    private const TYPES = ['Software', 'Network & Infrastructure', 'Training & Consultancy'];
    private const STATUSES = ['planning', 'active', 'risk', 'closed'];

    public function index(Request $request)
    {
        // Everyone with view_projects sees the full directory — read access
        // is intentionally broad; it's the write actions that are scoped.
        abort_unless(Auth::user()->can('view_projects'), 403);

        $query = Project::with(['team', 'budget', 'phases', 'memberRoles']);

        if ($type = $request->get('type')) {
            $query->where('project_type', $type);
        }

        $projects = $query->orderByDesc('project_id')->paginate(15);

        return view('projects.index', compact('projects'));
    }

      public function show(Project $project)
    {
        abort_unless(Auth::user()->can('view_projects'), 403);

        $project->load([
            'team.members.user', 'budget', 'phases.budget', 'phases.tasks.assignee',
            'deliverables', 'changeRequests.requester',
        ]);

        $tasks = collect($project->phases)->flatMap->tasks;

        $assignableUsers = collect();
        if ($project->team) {
            $assignableUsers = $project->team->members
                ->pluck('user')->filter()
                ->map(fn ($u) => ['id' => $u->user_id, 'name' => $u->full_name])
                ->values();
        }

        return view('projects.show', compact('project', 'tasks', 'assignableUsers'));
    }

    public function create()
    {
        abort_unless(Auth::user()->can('create_projects'), 403);

        return view('projects.create', [
            'teams' => $this->eligibleTeamsFor(Auth::user()),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('create_projects'), 403);

        $eligibleTeamIds = $this->eligibleTeamsFor($user)->pluck('team_id');

        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_type' => ['required', 'in:' . implode(',', self::TYPES)],
            // A Team Leader can only create a project under a team they actually
            // lead — a Director/Admin can pick any team. Enforced here, not just
            // in the dropdown, so a forged request can't pick someone else's team.
            'team_id' => ['required', Rule::in($eligibleTeamIds)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project = Project::create([
            'project_name' => $data['project_name'],
            'description' => $data['description'] ?? null,
            'project_type' => $data['project_type'],
            'team_id' => $data['team_id'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => 'planning',
            'created_by' => $user->user_id,
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'allocated_amount' => $data['allocated_amount'] ?? 0,
            'spent_amount' => 0,
            'currency' => 'ETB',
        ]);

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

        Activity::log('Created project', 'Project', $project->project_id, $project->project_name);

        if ($project->team->team_leader_id && $project->team->team_leader_id !== $user->user_id) {
            Activity::notify($project->team->team_leader_id, $user->full_name . " created a new project: \"{$project->project_name}\"", 'project');
        }

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project)
    {
        $user = Auth::user();
        abort_unless($user->can('edit_projects') && $project->isManagedBy($user), 403);

        return view('projects.edit', [
            'project' => $project->load('budget'),
            'teams' => $this->eligibleTeamsFor($user, $project),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'canEditBudget' => $user->can('manage_budgets'),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $user = Auth::user();
        abort_unless($user->can('edit_projects') && $project->isManagedBy($user), 403);

        $eligibleTeamIds = $this->eligibleTeamsFor($user, $project)->pluck('team_id');

        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'team_id' => ['required', Rule::in($eligibleTeamIds)],
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project->update([
            'project_name' => $data['project_name'],
            'description' => $data['description'] ?? null,
            'project_type' => $data['project_type'],
            'team_id' => $data['team_id'],
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        // Budget figures need manage_budgets specifically (ICT Director by
        // default) — a team leader can edit everything else, not this.
        if (isset($data['allocated_amount']) && $project->budget && $user->can('manage_budgets')) {
            $previous = $project->budget->allocated_amount;
            $project->budget->update(['allocated_amount' => $data['allocated_amount']]);
            if ((float) $previous !== (float) $data['allocated_amount']) {
                Activity::log('Updated project budget', 'Project', $project->project_id, "{$project->project_name}: ETB " . number_format($previous) . ' → ETB ' . number_format($data['allocated_amount']));
            }
        }

        Activity::log('Updated project', 'Project', $project->project_id, $project->project_name);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $user = Auth::user();
        abort_unless($user->can('delete_projects') && $project->isManagedBy($user), 403);

        $name = $project->project_name;
        Activity::log('Deleted project', 'Project', $project->project_id, $name);
        $project->delete();

        return redirect()->route('projects.index')->with('status', "\"{$name}\" was deleted.");
    }

    public function storeChangeRequest(Request $request, Project $project)
    {
        abort_unless(Auth::user()->can('view_projects'), 403);

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
            Activity::notify($project->team->team_leader_id, Auth::user()->full_name . " filed a change request on \"{$project->project_name}\"", 'approval');
        }

        return back()->with('status', 'Change request submitted.');
    }

    /**
     * Teams this user is allowed to create/assign a project under: any team
     * for a Director/Admin (or anyone editing a project they already manage),
     * otherwise only teams they actually lead.
     */
    private function eligibleTeamsFor($user, ?Project $editingProject = null)
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
