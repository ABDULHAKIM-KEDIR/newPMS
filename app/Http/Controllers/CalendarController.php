<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view_calendar') || Auth::user()->isDirectorOrAdmin(), 403);

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $projectId = $request->get('project_id');
        $teamId = $request->get('team_id');

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // Tasks query
        $tasksQuery = Task::with(['project', 'team', 'assignee'])
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('end_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
            });

        if ($projectId) {
            $tasksQuery->where('project_id', $projectId);
        }
        if ($teamId) {
            $tasksQuery->where('team_id', $teamId);
        }

        $tasks = $tasksQuery->get();

        // Projects query
        $projectsQuery = Project::where(function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('end_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->orWhereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
        });
        if ($projectId) {
            $projectsQuery->where('project_id', $projectId);
        }
        $projectsInMonth = $projectsQuery->get();

        $allProjects = Project::orderBy('project_name')->get();
        $allTeams = Team::where('status', 'Active')->orderBy('team_name')->get();

        // Group tasks by date for fast calendar cell lookup
        $tasksByDate = [];
        foreach ($tasks as $task) {
            if ($task->end_date) {
                $dateKey = $task->end_date->format('Y-m-d');
                $tasksByDate[$dateKey][] = $task;
            }
        }

        $projectsByDate = [];
        foreach ($projectsInMonth as $proj) {
            if ($proj->end_date) {
                $dateKey = $proj->end_date->format('Y-m-d');
                $projectsByDate[$dateKey][] = $proj;
            }
        }

        return view('calendar.index', compact(
            'currentDate',
            'prevMonth',
            'nextMonth',
            'year',
            'month',
            'tasksByDate',
            'projectsByDate',
            'tasks',
            'allProjects',
            'allTeams',
            'projectId',
            'teamId'
        ));
    }
}
