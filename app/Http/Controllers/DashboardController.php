<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $scoped = ! $user->isDirectorOrAdmin();
        $teamIds = $scoped ? $user->teamIds() : null;

        $projectQuery = Project::with(['team', 'budget', 'phases']);
        $budgetQuery = ProjectBudget::query();
        $taskQuery = Task::query();

        if ($scoped) {
            $projectQuery->whereIn('team_id', $teamIds);
            $budgetQuery->whereHas('project', fn ($q) => $q->whereIn('team_id', $teamIds));
            $taskQuery->whereHas('phase.project', fn ($q) => $q->whereIn('team_id', $teamIds));
        }

        $projects = (clone $projectQuery)->orderByDesc('project_id')->take(4)->get();

        $stats = [
            'active_projects' => (clone $projectQuery)->whereNotIn('status', ['closed'])->count(),
            'open_tasks' => (clone $taskQuery)->where('status', '!=', 'Done')->count(),
            'overdue_tasks' => (clone $taskQuery)->where('status', '!=', 'Done')->whereDate('end_date', '<', now())->count(),
            'budget_allocated' => (float) (clone $budgetQuery)->sum('allocated_amount'),
            'budget_spent' => (float) (clone $budgetQuery)->sum('spent_amount'),
            'pending_change_requests' => $scoped
                ? ChangeRequest::where('status', 'Pending')->whereHas('project', fn ($q) => $q->whereIn('team_id', $teamIds))->count()
                : ChangeRequest::where('status', 'Pending')->count(),
        ];

        // The audit log isn't team-tagged in a way that's safe to filter
        // precisely, and it's already a directorate-wide transparency page —
        // show the same recent activity to everyone rather than fake-scoping it.
        $activity = AuditLog::with('user')->orderByDesc('timestamp')->take(6)->get();

        $teamLoadQuery = User::withCount(['assignedTasks as open_task_count' => function ($q) {
            $q->where('status', '!=', 'Done');
        }]);
        if ($scoped) {
            $teamLoadQuery->whereHas('teamMemberships', fn ($q) => $q->whereIn('team_id', $teamIds));
        }
        $teamLoad = $teamLoadQuery->orderByDesc('open_task_count')->take(4)->get();

        return view('dashboard', compact('projects', 'stats', 'activity', 'teamLoad', 'scoped'));
    }
}
