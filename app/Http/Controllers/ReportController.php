<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view_reports') || Auth::user()->isDirectorOrAdmin(), 403);

        $projectId = $request->get('project_id');
        $teamId = $request->get('team_id');

        $projectsQuery = Project::with(['tasks', 'phases.tasks', 'projectManager', 'team', 'teams']);
        if ($projectId) {
            $projectsQuery->where('project_id', $projectId);
        }
        $projects = $projectsQuery->get();

        $tasksQuery = Task::with(['project', 'team', 'assignee']);
        if ($projectId) {
            $tasksQuery->where('project_id', $projectId);
        }
        if ($teamId) {
            $tasksQuery->where('team_id', $teamId);
        }
        $tasks = $tasksQuery->get();

        $teams = Team::with(['leader', 'members.user', 'tasks'])->get();

        // High level metrics
        $totalProjects = $projects->count();
        $completedProjects = $projects->filter(fn ($p) => in_array(strtolower((string) $p->status), ['completed', 'closed']))->count();
        $inProgressProjects = $projects->filter(fn ($p) => in_array(strtolower((string) $p->status), ['active', 'in progress']))->count();
        $pendingProjects = $projects->filter(fn ($p) => in_array(strtolower((string) $p->status), ['planning', 'pending', 'draft']))->count();
        $activeProjects = $inProgressProjects;

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(fn ($t) => in_array($t->status, ['Done', 'Completed']))->count();
        $inProgressTasks = $tasks->filter(fn ($t) => $t->status === 'In Progress')->count();
        $inReviewTasks = $tasks->filter(fn ($t) => $t->status === 'In Review')->count();
        $toDoTasks = $tasks->filter(fn ($t) => in_array($t->status, ['Pending', 'To Do', 'Not started']))->count();
        $blockedTasks = $tasks->filter(fn ($t) => $t->status === 'Blocked')->count();
        $rejectedTasks = $tasks->filter(fn ($t) => $t->assignments()->where('acceptance_status', 'Rejected')->exists())->count();
        $overdueTasks = $tasks->filter(fn ($t) => ! in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast())->count();

        // Payments & Costs reporting (Requirement 13)
        $totalCost = (float) Payment::where('payment_status', 'Completed')->sum('amount');
        if ($totalCost === 0.0) {
            $totalCost = (float) ProjectBudget::sum('spent_amount');
        }

        $paymentsByProject = $projects->map(function ($p) {
            $spent = $p->calculateTotalCost();
            $allocated = (float) (optional($p->budget)->allocated_amount ?? 0);

            return [
                'project_id' => $p->project_id,
                'name' => $p->project_name,
                'allocated' => $allocated,
                'spent' => $spent,
                'utilization' => $allocated > 0 ? round(($spent / $allocated) * 100) : 0,
            ];
        });

        // Users by Office (Requirement 13)
        $usersByOffice = Office::withCount('users')->get()->map(fn ($o) => [
            'office_id' => $o->office_id,
            'name' => $o->office_name,
            'code' => $o->office_code,
            'count' => $o->users_count,
        ]);

        // Projects by Department / Office (Requirement 13)
        $projectsByDepartment = Office::withCount('primaryProjects')->get()->map(fn ($o) => [
            'department' => $o->office_name,
            'count' => $o->primary_projects_count,
        ]);

        $overallProgress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

        // Tasks by Priority
        $priorityStats = [
            'High' => $tasks->where('priority', 'High')->count(),
            'Medium' => $tasks->where('priority', 'Medium')->count(),
            'Low' => $tasks->where('priority', 'Low')->count(),
            'Urgent' => $tasks->where('priority', 'Urgent')->count(),
        ];

        // Tasks by Status
        $statusStats = [
            'To Do' => $toDoTasks,
            'In Progress' => $inProgressTasks,
            'In Review' => $inReviewTasks,
            'Completed' => $completedTasks,
            'Blocked' => $blockedTasks,
        ];

        // Team performance & workload
        $teamWorkload = $teams->map(function ($team) {
            $stats = $team->taskStats();

            return [
                'team_id' => $team->team_id,
                'team_name' => $team->team_name,
                'leader_name' => optional($team->leader)->full_name ?? 'Unassigned',
                'members_count' => $team->members->count(),
                'projects_count' => $team->allProjects()->count(),
                'total_tasks' => $stats['total'],
                'completed_tasks' => $stats['completed'],
                'in_progress' => $stats['in_progress'],
                'overdue_tasks' => $stats['overdue'],
                'progress' => $stats['progress'],
            ];
        });

        // Individual member workload
        $users = User::where('status', 'Active')->with('assignedTasks')->get();
        $memberWorkload = $users->map(function ($u) {
            $userTasks = $u->assignedTasks;
            $total = $userTasks->count();
            $done = $userTasks->filter(fn ($t) => in_array($t->status, ['Done', 'Completed']))->count();
            $pending = $total - $done;
            $overdue = $userTasks->filter(fn ($t) => ! in_array($t->status, ['Done', 'Completed']) && $t->end_date && $t->end_date->isPast())->count();

            return [
                'user_id' => $u->user_id,
                'name' => $u->full_name,
                'department' => $u->department ?: 'General',
                'initials' => $u->initials(),
                'total' => $total,
                'done' => $done,
                'pending' => $pending,
                'overdue' => $overdue,
                'rate' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ];
        })->filter(fn ($m) => $m['total'] > 0)->sortByDesc('total')->values();

        // Upcoming Project Deadlines
        $upcomingDeadlines = $projects->filter(fn ($p) => $p->end_date)->sortBy('end_date')->take(6)->values();

        return view('reports.index', compact(
            'projects',
            'teams',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'inProgressProjects',
            'pendingProjects',
            'totalTasks',
            'completedTasks',
            'inProgressTasks',
            'inReviewTasks',
            'toDoTasks',
            'blockedTasks',
            'rejectedTasks',
            'overdueTasks',
            'overallProgress',
            'priorityStats',
            'statusStats',
            'teamWorkload',
            'memberWorkload',
            'upcomingDeadlines',
            'totalCost',
            'paymentsByProject',
            'usersByOffice',
            'projectsByDepartment',
            'projectId',
            'teamId'
        ));
    }
}
