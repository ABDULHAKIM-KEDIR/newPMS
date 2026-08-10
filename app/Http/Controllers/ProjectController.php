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

class ProjectController extends Controller
{
    private const PHASES = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
    private const TYPES = ['Software', 'Network & Infrastructure', 'Training & Consultancy'];
    private const STATUSES = ['planning', 'active', 'risk', 'closed'];

    public function index(Request $request)
    {
        $query = Project::with(['team', 'budget', 'phases', 'memberRoles']);

        if ($type = $request->get('type')) {
            $query->where('project_type', $type);
        }

        $projects = $query->orderByDesc('project_id')->paginate(15);

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load([
            'team', 'budget', 'phases.budget', 'phases.tasks.assignee',
            'deliverables', 'changeRequests.requester',
        ]);

        $tasks = collect($project->phases)->flatMap->tasks;

        return view('projects.show', compact('project', 'tasks'));
    }

    public function create()
    {
        $teams = Team::orderBy('team_name')->get();

        return view('projects.create', ['teams' => $teams, 'types' => self::TYPES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'team_id' => ['required', 'exists:teams,team_id'],
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
            'created_by' => Auth::id(),
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

        if ($project->team->team_leader_id) {
            Activity::notify($project->team->team_leader_id, Auth::user()->full_name . " created a new project: \"{$project->project_name}\"", 'project');
        }

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project)
    {
        $teams = Team::orderBy('team_name')->get();

        return view('projects.edit', [
            'project' => $project->load('budget'),
            'teams' => $teams,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'team_id' => ['required', 'exists:teams,team_id'],
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

        if (isset($data['allocated_amount']) && $project->budget) {
            $project->budget->update(['allocated_amount' => $data['allocated_amount']]);
        }

        Activity::log('Updated project', 'Project', $project->project_id, $project->project_name);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function storeChangeRequest(Request $request, Project $project)
    {
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
}
