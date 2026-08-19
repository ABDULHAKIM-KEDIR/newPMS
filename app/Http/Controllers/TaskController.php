<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProgressLog;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    private const STATUSES = ['Pending', 'In Progress', 'Done'];

    // "My Tasks" — tasks assigned to the current user
    public function index(Request $request)
    {
        $userId = Auth::id();

        $tasks = Task::with(['phase.project', 'assignee'])
            ->where('assigned_to', $userId)
            ->orderBy('end_date')
            ->get();

        return view('tasks.index', compact('tasks'));
    }

    public function show(Task $task)
    {
        $task->load(['subtasks', 'comments.user', 'dependencies', 'assignee', 'phase.project.team']);
        $user = Auth::user();
        $project = $task->phase->project;

        $canManage = $project->isManagedBy($user);
        $canUpdateStatus = $canManage || $task->assigned_to === $user->user_id;

        $assignableUsers = [];
        if ($canManage && $project->team) {
            $assignableUsers = $project->team->members()
                ->with('user')->get()
                ->pluck('user')->filter()
                ->map(fn ($u) => ['id' => $u->user_id, 'name' => $u->full_name])
                ->values();
        }

        return response()->json([
            'id' => $task->task_id,
            'name' => $task->task_name,
            'status' => $task->status,
            'statuses' => self::STATUSES,
            'priority' => $task->priority,
            'assignee' => optional($task->assignee)->full_name,
            'assignee_id' => $task->assigned_to,
            'phase' => optional($task->phase)->phase_name,
            'due' => optional($task->end_date)?->format('d M Y'),
            'description' => $task->description,
            'can_update_status' => $canUpdateStatus,
            'can_manage' => $canManage,
            'assignable_users' => $assignableUsers,
            'subtasks' => $task->subtasks->map(fn ($t) => ['name' => $t->task_name, 'status' => $t->status]),
            'comments' => $task->comments->map(fn ($c) => [
                'user' => optional($c->user)->full_name,
                'text' => $c->comment_text,
                'at' => $c->created_at?->diffForHumans(),
            ]),
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $task->load('phase.project.team');
        $project = $task->phase->project;
        $user = Auth::user();

        $isAssignee = $task->assigned_to === $user->user_id;
        abort_unless($user->can('update_task_status') && ($isAssignee || $project->isManagedBy($user)), 403);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
        ]);

        $previous = $task->status;
        $task->update(['status' => $data['status']]);

        TaskProgressLog::create([
            'task_id' => $task->task_id,
            'user_id' => $user->user_id,
            'previous_status' => $previous,
            'new_status' => $data['status'],
        ]);

        Activity::log('Updated task status', 'Task', $task->task_id, "{$previous} → {$data['status']} ({$task->task_name})");

        // Notify the other side of the assignment: if the assignee changed
        // it, tell whoever leads the team; if a manager changed it, tell the assignee.
        if ($task->assigned_to && $task->assigned_to !== $user->user_id) {
            Activity::notify($task->assigned_to, "\"{$task->task_name}\" was moved to {$data['status']} by " . $user->full_name, 'task');
        } elseif (optional($project->team)->team_leader_id && $project->team->team_leader_id !== $user->user_id) {
            Activity::notify($project->team->team_leader_id, $user->full_name . " moved \"{$task->task_name}\" to {$data['status']}", 'task');
        }

        return response()->json(['status' => $task->status]);
    }

    public function addComment(Request $request, Task $task)
    {
        $data = $request->validate([
            'comment_text' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();

        $comment = TaskComment::create([
            'task_id' => $task->task_id,
            'user_id' => $user->user_id,
            'comment_text' => $data['comment_text'],
        ]);

        Activity::log('Commented on task', 'Task', $task->task_id);

        if ($task->assigned_to && $task->assigned_to !== $user->user_id) {
            Activity::notify($task->assigned_to, $user->full_name . " commented on \"{$task->task_name}\"", 'mention');
        }

        return response()->json([
            'user' => $user->full_name,
            'text' => $comment->comment_text,
            'at' => $comment->created_at->diffForHumans(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('create_tasks'), 403);

        $data = $request->validate([
            'phase_id' => ['required', 'exists:phases,phase_id'],
            'task_name' => ['required', 'string', 'max:150'],
            'assigned_to' => ['nullable', 'exists:users,user_id'],
            'priority' => ['required', 'in:High,Medium,Low'],
            'end_date' => ['nullable', 'date'],
        ]);

        $phase = \App\Models\Phase::with('project')->findOrFail($data['phase_id']);
        abort_unless($phase->project->isManagedBy($user), 403);

        $task = Task::create([
            'phase_id' => $phase->phase_id,
            'task_name' => $data['task_name'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'priority' => $data['priority'],
            'status' => 'Pending',
            'end_date' => $data['end_date'] ?? null,
        ]);

        Activity::log('Created task', 'Task', $task->task_id, "{$task->task_name} on {$phase->project->project_name}");

        if ($task->assigned_to) {
            Activity::notify($task->assigned_to, $user->full_name . " assigned you a new task: \"{$task->task_name}\"", 'task');
        }

        return back()->with('status', 'Task added.');
    }

    public function assign(Request $request, Task $task)
    {
        $task->load('phase.project');
        $project = $task->phase->project;
        $user = Auth::user();

        abort_unless($user->can('assign_tasks') && $project->isManagedBy($user), 403);

        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,user_id'],
        ]);

        $task->update(['assigned_to' => $data['assigned_to']]);

        Activity::log('Reassigned task', 'Task', $task->task_id, "{$task->task_name} → user #{$data['assigned_to']}");
        Activity::notify((int) $data['assigned_to'], $user->full_name . " assigned you \"{$task->task_name}\"", 'task');

        return response()->json(['assignee_id' => $task->assigned_to]);
    }
}
