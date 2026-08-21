<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProgressLog;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    private const STATUSES = ['Pending', 'In Progress', 'Done'];

    // Tasks list — supporting "My Tasks" (default) and "All Tasks"
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'mine');
        $status = $request->get('status');
        $search = $request->get('q');

        $query = Task::with(['phase.project', 'assignee'])->orderBy('end_date');

        if ($filter === 'mine' || ! $user->can('view_projects')) {
            $query->where('assigned_to', $user->user_id);
        }

        if ($status && in_array($status, self::STATUSES)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('task_name', 'like', "%{$search}%");
        }

        $tasks = $query->get();

        $myCount = Task::where('assigned_to', $user->user_id)->count();
        $allCount = Task::count();

        return view('tasks.index', compact('tasks', 'filter', 'status', 'search', 'myCount', 'allCount'));
    }

    public function show(Task $task)
    {
        $task->load(['subtasks', 'comments.user', 'dependencies', 'assignee', 'phase.project.team']);

        $attachments = $task->attachments()->with('uploader')->get()->map(function ($att) use ($task) {
            return [
                'id' => $att->attachment_id,
                'file_name' => $att->file_name,
                'file_url' => route('tasks.attachments.download', [$task->task_id, $att->attachment_id]),
                'uploader' => optional($att->uploader)->full_name,
                'uploader_id' => $att->uploaded_by,
                'created_at' => $att->created_at ? $att->created_at->diffForHumans() : null,
            ];
        });

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
            'attachments' => $attachments,
            'current_user_id' => $user->user_id,
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
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
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
            Activity::notify($task->assigned_to, "\"{$task->task_name}\" was moved to {$data['status']} by ".$user->full_name, 'task');
        } elseif (optional($project->team)->team_leader_id && $project->team->team_leader_id !== $user->user_id) {
            Activity::notify($project->team->team_leader_id, $user->full_name." moved \"{$task->task_name}\" to {$data['status']}", 'task');
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
            Activity::notify($task->assigned_to, $user->full_name." commented on \"{$task->task_name}\"", 'mention');
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
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,user_id'],
            'priority' => ['required', 'in:High,Medium,Low'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->filled('start_date') && $value < $request->input('start_date')) {
                        $fail('The end date must be a date after or equal to start date.');
                    }
                },
            ],
        ]);

        $phase = Phase::with('project')->findOrFail($data['phase_id']);
        $canManage = $phase->project->isManagedBy($user) || $user->can('create_tasks');
        abort_unless($canManage, 403);

        $task = Task::create([
            'phase_id' => $phase->phase_id,
            'task_name' => $data['task_name'],
            'description' => $data['description'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'priority' => $data['priority'],
            'status' => $data['status'] ?? 'Pending',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        Activity::log('Created task', 'Task', $task->task_id, "{$task->task_name} on {$phase->project->project_name}");

        if ($task->assigned_to) {
            Activity::notify($task->assigned_to, $user->full_name." assigned you a new task: \"{$task->task_name}\"", 'task');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Task added successfully.',
                'task' => $task->load(['assignee', 'phase.project']),
            ], 201);
        }

        return back()->with('status', 'Task added successfully.');
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
        Activity::notify((int) $data['assigned_to'], $user->full_name." assigned you \"{$task->task_name}\"", 'task');

        return response()->json(['assignee_id' => $task->assigned_to]);
    }

    public function update(Request $request, Task $task)
    {
        $task->load('phase.project');
        $project = $task->phase->project;
        $user = Auth::user();

        $canManage = $project->isManagedBy($user);
        $isAssignee = $task->assigned_to === $user->user_id;

        abort_unless($user->can('create_tasks') || $canManage || $isAssignee, 403);

        $data = $request->validate([
            'task_name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phase_id' => ['sometimes', 'required', 'exists:phases,phase_id'],
            'assigned_to' => ['nullable', 'exists:users,user_id'],
            'priority' => ['sometimes', 'required', 'in:High,Medium,Low'],
            'status' => ['sometimes', 'required', 'in:'.implode(',', self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $task->update($data);

        Activity::log('Updated task', 'Task', $task->task_id, $task->task_name);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Task updated successfully',
                'task' => $task,
            ]);
        }

        return back()->with('status', 'Task updated successfully.');
    }

    public function destroy(Request $request, Task $task)
    {
        $task->load('phase.project');
        $project = $task->phase->project;
        $user = Auth::user();

        $canManage = $project->isManagedBy($user) || $user->can('create_tasks');
        abort_unless($canManage, 403);

        $taskName = $task->task_name;
        Activity::log('Deleted task', 'Task', $task->task_id, $taskName);

        $task->comments()->delete();
        $task->progressLogs()->delete();
        $task->dependencies()->detach();
        $task->dependents()->detach();
        $task->subtasks()->update(['parent_task_id' => null]);
        $task->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Task deleted successfully']);
        }

        return back()->with('status', "\"{$taskName}\" was deleted.");
    }
}
