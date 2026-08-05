<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
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
}
