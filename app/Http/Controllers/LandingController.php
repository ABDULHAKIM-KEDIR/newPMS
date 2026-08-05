<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

class LandingController extends Controller
{
    public function index()
    {
        $stats = [
            'projects' => Project::count(),
            'teams' => Team::count(),
            'tasks_done' => Task::where('status', 'Done')->count(),
            'members' => User::count(),
        ];

        return view('landing', compact('stats'));
    }
}
