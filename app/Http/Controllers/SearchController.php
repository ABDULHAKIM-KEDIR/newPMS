<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $projects = collect();
        $tasks = collect();
        $people = collect();

        if ($q !== '') {
            $projects = Project::with('team')
                ->where('project_name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->limit(10)->get();

            $tasks = Task::with(['phase.project', 'assignee'])
                ->where('task_name', 'like', "%{$q}%")
                ->limit(10)->get();

            $people = User::where('full_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->limit(10)->get();
        }

        return view('search.index', compact('q', 'projects', 'tasks', 'people'));
    }
}
