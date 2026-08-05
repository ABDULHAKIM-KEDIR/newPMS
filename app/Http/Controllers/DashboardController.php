<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Project::with(['team', 'budget', 'phases'])
            ->orderByDesc('project_id')
            ->take(4)
            ->get();

        $stats = [
            'active_projects' => Project::whereNotIn('status', ['Closed'])->count(),
            'open_tasks' => Task::where('status', '!=', 'Done')->count(),
            'overdue_tasks' => Task::where('status', '!=', 'Done')->whereDate('end_date', '<', now())->count(),
            'budget_allocated' => (float) \App\Models\ProjectBudget::sum('allocated_amount'),
            'budget_spent' => (float) \App\Models\ProjectBudget::sum('spent_amount'),
            'pending_change_requests' => ChangeRequest::where('status', 'Pending')->count(),
        ];

        $activity = AuditLog::with('user')->orderByDesc('timestamp')->take(6)->get();

        $teamLoad = User::withCount(['assignedTasks as open_task_count' => function ($q) {
            $q->where('status', '!=', 'Done');
        }])->orderByDesc('open_task_count')->take(4)->get();

        return view('dashboard', compact('projects', 'stats', 'activity', 'teamLoad'));
    }
}
