<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Phase;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProgressLog;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public const STATUSES = ['To Do', 'In Progress', 'In Review', 'Completed', 'Blocked', 'Pending', 'Done'];

    // Tasks list — supporting "My Tasks" (default) and "All Tasks", Kanban & List views
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'mine');
        $view = $request->get('view', 'kanban'); // 'kanban' or 'list'
        $status = $request->get('status');
        $priority = $request->get('priority');
        $search = $request->get('q');
        $projectId = $request->get('project');
        $teamId = $request->get('team');
        $assigneeId = $request->get('assignee');
        $dueDate = $request->get('due_date');

        $query = Task::with(['project', 'team', 'phase.project', 'assignee', 'comments', 'attachments', 'subtasks'])->orderBy('end_date');

        // Scoping: "mine" vs "all"
        if ($filter === 'mine' || ! $user->can('view_projects')) {
            $query->where('assigned_to', $user->user_id);
        }

        if ($status) {
            if ($status === 'To Do') {
                $query->whereIn('status', ['To Do', 'Pending', 'Not started']);
            } elseif ($status === 'Completed') {
                $query->whereIn('status', ['Completed', 'Done']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where('task_name', 'like', "%{$search}%");
        }

        if ($projectId) {
            $query->where(function ($q) use ($projectId) {
                $q->where('project_id', $projectId)
                    ->orWhereHas('phase', fn ($pq) => $pq->where('project_id', $projectId));
            });
        }

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($assigneeId && $filter === 'all') {
            $query->where('assigned_to', $assigneeId);
        }

        if ($dueDate) {
            $query->whereDate('end_date', $dueDate);
        }

        $tasks = $query->get();

        $myCount = Task::where('assigned_to', $user->user_id)->count();
        $allCount = Task::count();

        $projects = Project::orderBy('project_name')->get();
        $teams = Team::where('status', 'Active')->orderBy('team_name')->get();
        $assignableUsers = User::where('status', 'Active')->orderBy('full_name')->get();

        return view('tasks.index', compact(
            'tasks', 'filter', 'view', 'status', 'priority', 'search',
            'projectId', 'teamId', 'assigneeId', 'dueDate',
            'myCount', 'allCount', 'projects', 'teams', 'assignableUsers'
        ));
    }

    public function show(Task $task)
    {
        $task->load([
            'project.teams', 'team.members.user', 'subtasks', 'comments.user',
            'attachments.uploader', 'dependencies', 'assignee', 'phase.project.team', 'progressLogs.user',
        ]);

        $user = Auth::user();
        $project = $task->project ?? optional($task->phase)->project;

        $canManage = ($project && $project->isManagedBy($user)) || $user->can('create_tasks');
        $canUpdateStatus = $canManage || $task->assigned_to === $user->user_id;

        $assignableUsers = $project ? $project->getAssignableUsersWithRoles() : collect();

        $phases = [];
        if ($project) {
            $phases = $project->phases->map(fn ($ph) => ['id' => $ph->phase_id, 'name' => $ph->phase_name])->values();
        }

        return response()->json([
            'id' => $task->task_id,
            'name' => $task->task_name,
            'status' => $task->status,
            'statuses' => ['To Do', 'In Progress', 'In Review', 'Completed', 'Blocked'],
            'priority' => $task->priority,
            'progress' => $task->progress ?: (in_array($task->status, ['Done', 'Completed']) ? 100 : 0),
            'budget' => (float) ($task->budget ?: 0),
            'budget_formatted' => number_format((float) ($task->budget ?: 0)),
            'assignee' => optional($task->assignee)->full_name,
            'assignee_name' => optional($task->assignee)->full_name,
            'assignee_id' => $task->assigned_to,
            'team_id' => $task->team_id,
            'team_name' => optional($task->team)->team_name,
            'phase_id' => $task->phase_id,
            'phase' => optional($task->phase)->phase_name,
            'phases' => $phases,
            'project_id' => $project ? $project->project_id : null,
            'project' => $project ? $project->project_name : null,
            'project_url' => $project ? route('projects.show', $project) : '#',
            'start_date' => optional($task->start_date)?->format('Y-m-d'),
            'end_date' => optional($task->end_date)?->format('Y-m-d'),
            'start_date_formatted' => optional($task->start_date)?->format('d M Y'),
            'end_date_formatted' => optional($task->end_date)?->format('d M Y'),
            'due' => optional($task->end_date)?->format('d M Y'),
            'is_overdue' => $task->isOverdue(),
            'blocker_reason' => $task->blocker_reason,
            'description' => $task->description,
            'can_update_status' => $canUpdateStatus,
            'can_manage' => $canManage,
            'assignable_users' => $assignableUsers,
            'subtasks' => $task->subtasks->map(fn ($t) => [
                'id' => $t->task_id,
                'name' => $t->task_name,
                'status' => $t->status,
                'is_completed' => in_array($t->status, ['Done', 'Completed']),
            ]),
            'attachments' => $task->attachments->map(fn ($a) => [
                'id' => $a->attachment_id,
                'file_name' => $a->file_name,
                'file_url' => Storage::url($a->file_path),
                'uploader' => optional($a->uploader)->full_name ?? 'User',
                'uploaded_at' => optional($a->uploaded_at)->diffForHumans() ?? 'Recently',
            ]),
            'comments' => $task->comments->map(fn ($c) => [
                'id' => $c->comment_id,
                'user' => optional($c->user)->full_name,
                'avatar' => optional($c->user)->avatar,
                'text' => $c->comment_text,
                'at' => $c->created_at?->diffForHumans() ?? 'Just now',
            ]),
            'activity_logs' => $task->progressLogs->map(fn ($l) => [
                'user' => optional($l->user)->full_name ?? 'User',
                'from' => $l->previous_status,
                'to' => $l->new_status,
                'remarks' => $l->remarks,
                'at' => $l->changed_at ? Carbon::parse($l->changed_at)->diffForHumans() : '',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('create_tasks'), 403);

        $phase = null;
        $project = null;

        if ($request->filled('phase_id')) {
            $phase = Phase::with('project')->find($request->input('phase_id'));
            if ($phase) {
                $project = $phase->project;
            }
        } elseif ($request->filled('project_id')) {
            $project = Project::with('phases')->find($request->input('project_id'));
            if ($project && $project->phases->isNotEmpty()) {
                $phase = $project->phases->first();
            }
        }

        $canManage = $project ? ($project->isManagedBy($user) || $user->can('create_tasks')) : $user->can('create_tasks');
        abort_unless($canManage, 403);

        $assigneeInput = $request->input('assigned_to') ?? $request->input('assignee_name') ?? $request->input('assignee_input');
        $resolvedAssigneeId = $this->resolveAssigneeId($assigneeInput, $project);

        $data = $request->validate([
            'project_id' => ['nullable', 'exists:projects,project_id'],
            'phase_id' => ['nullable', 'exists:phases,phase_id'],
            'team_id' => ['nullable', 'exists:teams,team_id'],
            'task_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:High,Medium,Low,Urgent'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
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

        $status = $data['status'] ?? 'To Do';
        $taskProjectId = $project ? $project->project_id : ($data['project_id'] ?? null);
        $taskTeamId = $data['team_id'] ?? ($project ? $project->team_id : null);

        $task = Task::create([
            'project_id' => $taskProjectId,
            'phase_id' => $phase ? $phase->phase_id : ($data['phase_id'] ?? null),
            'team_id' => $taskTeamId,
            'task_name' => $data['task_name'],
            'description' => $data['description'] ?? null,
            'assigned_to' => $resolvedAssigneeId,
            'priority' => $data['priority'],
            'status' => $status,
            'budget' => $data['budget'] ?? 0,
            'progress' => in_array($status, ['Done', 'Completed']) ? 100 : ($data['progress'] ?? 0),
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        if ($project) {
            $project->recalculateProgress();
        }

        Activity::log('Created task', 'Task', $task->task_id, $task->task_name);

        if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->user_id) {
            $projName = $project ? " on {$project->project_name}" : '';
            Activity::notify($task->assigned_to, $user->full_name." assigned you a new task: \"{$task->task_name}\"{$projName}", 'task');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Task added successfully.',
                'task' => $task->load(['assignee', 'phase.project', 'project', 'team']),
            ], 201);
        }

        return back()->with('status', 'Task added successfully.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $task->load('phase.project.team', 'project');
        $project = $task->project ?? optional($task->phase)->project;
        $user = Auth::user();

        $isAssignee = (int) $task->assigned_to === (int) $user->user_id;
        abort_unless($user->can('update_task_status') && ($isAssignee || ($project && $project->isManagedBy($user)) || $user->isDirectorOrAdmin()), 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'blocker_reason' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $previous = $task->status;
        $newStatus = $data['status'];
        $progress = in_array($newStatus, ['Done', 'Completed']) ? 100 : ($newStatus === 'To Do' ? 0 : $task->progress);
        $blockerReason = $newStatus === 'Blocked' ? ($data['blocker_reason'] ?? $task->blocker_reason) : null;

        $task->update([
            'status' => $newStatus,
            'progress' => $progress,
            'blocker_reason' => $blockerReason,
        ]);

        $logRemarks = $data['remarks'] ?? ($newStatus === 'Blocked' ? "Blocker: {$blockerReason}" : ($previous === 'Blocked' ? 'Blocker resolved' : null));

        TaskProgressLog::create([
            'task_id' => $task->task_id,
            'user_id' => $user->user_id,
            'previous_status' => $previous,
            'new_status' => $newStatus,
            'remarks' => $logRemarks,
        ]);

        if ($project) {
            $project->recalculateProgress();
        }

        Activity::log('Updated task status', 'Task', $task->task_id, "{$previous} → {$newStatus} ({$task->task_name})".($blockerReason ? " [Blocker: {$blockerReason}]" : ''));

        // Notification routing: notify assignee, team lead, and PM on blockers
        if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->user_id) {
            Activity::notify((int) $task->assigned_to, "\"{$task->task_name}\" was moved to {$newStatus} by ".$user->full_name, 'task');
        }

        if ($newStatus === 'Blocked') {
            if ($project && $project->project_manager_id && (int) $project->project_manager_id !== (int) $user->user_id) {
                Activity::notify((int) $project->project_manager_id, "Task \"{$task->task_name}\" on {$project->project_name} was marked as Blocked".($blockerReason ? ": {$blockerReason}" : ''), 'task');
            }
            if ($task->team && $task->team->team_leader_id && (int) $task->team->team_leader_id !== (int) $user->user_id) {
                Activity::notify((int) $task->team->team_leader_id, "Team task \"{$task->task_name}\" was marked as Blocked".($blockerReason ? ": {$blockerReason}" : ''), 'task');
            }
        }

        return response()->json([
            'status' => $task->status,
            'progress' => $task->progress,
            'blocker_reason' => $task->blocker_reason,
            'project_progress' => $project ? $project->fresh()->progressPercentage() : null,
        ]);
    }

    public function assign(Request $request, Task $task)
    {
        $task->load('phase.project', 'project');
        $project = $task->project ?? optional($task->phase)->project;
        $user = Auth::user();

        abort_unless($user->can('assign_tasks') && (($project && $project->isManagedBy($user)) || $user->isDirectorOrAdmin()), 403);

        $assigneeInput = $request->input('assigned_to') ?? $request->input('assignee_name');
        $reason = $request->input('reason');
        $resolvedAssigneeId = $this->resolveAssigneeId($assigneeInput, $project);

        $previousAssignee = optional($task->assignee)->full_name ?? 'Unassigned';
        $task->update(['assigned_to' => $resolvedAssigneeId]);

        $assigneeName = optional($task->fresh()->assignee)->full_name ?? 'Unassigned';
        $remarks = "Reassigned from {$previousAssignee} to {$assigneeName}".($reason ? " (Reason: {$reason})" : '');

        TaskProgressLog::create([
            'task_id' => $task->task_id,
            'user_id' => $user->user_id,
            'previous_status' => $task->status,
            'new_status' => $task->status,
            'remarks' => $remarks,
        ]);

        Activity::log('Reassigned task', 'Task', $task->task_id, "{$task->task_name} → {$assigneeName}".($reason ? " (Reason: {$reason})" : ''));

        if ($resolvedAssigneeId && (int) $resolvedAssigneeId !== (int) $user->user_id) {
            Activity::notify((int) $resolvedAssigneeId, $user->full_name." assigned you \"{$task->task_name}\"".($reason ? " (Reason: {$reason})" : ''), 'task');
        }

        return response()->json([
            'assignee_id' => $task->assigned_to,
            'assignee' => $assigneeName,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $task->load('phase.project', 'project');
        $project = $task->project ?? optional($task->phase)->project;
        $user = Auth::user();

        $canManage = $project ? $project->isManagedBy($user) : false;
        $isAssignee = (int) $task->assigned_to === (int) $user->user_id;

        abort_unless($user->can('create_tasks') || $canManage || $isAssignee || $user->isDirectorOrAdmin(), 403);

        if ($request->has('start_date') && $request->input('start_date') === '') {
            $request->merge(['start_date' => null]);
        }
        if ($request->has('end_date') && $request->input('end_date') === '') {
            $request->merge(['end_date' => null]);
        }
        if ($request->has('description') && $request->input('description') === '') {
            $request->merge(['description' => null]);
        }

        $data = $request->validate([
            'task_name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phase_id' => ['nullable', 'exists:phases,phase_id'],
            'team_id' => ['nullable', 'exists:teams,team_id'],
            'priority' => ['sometimes', 'required', 'in:High,Medium,Low,Urgent'],
            'status' => ['sometimes', 'required', 'in:'.implode(',', self::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        if ($request->has('assigned_to') || $request->has('assignee_name')) {
            $assigneeInput = $request->input('assigned_to') ?? $request->input('assignee_name');
            $data['assigned_to'] = $this->resolveAssigneeId($assigneeInput, $project);
        }

        if (isset($data['status'])) {
            if (in_array($data['status'], ['Done', 'Completed'])) {
                $data['progress'] = 100;
            } elseif ($data['status'] === 'To Do' && ! isset($data['progress'])) {
                $data['progress'] = 0;
            }
        }

        $task->update($data);

        if ($project) {
            $project->recalculateProgress();
        }

        Activity::log('Updated task', 'Task', $task->task_id, $task->task_name);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Task updated successfully',
                'task' => $task->fresh(['assignee', 'phase.project', 'project', 'team']),
            ]);
        }

        return back()->with('status', 'Task updated successfully.');
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

        if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->user_id) {
            Activity::notify((int) $task->assigned_to, $user->full_name." commented on \"{$task->task_name}\"", 'mention');
        }

        return response()->json([
            'id' => $comment->comment_id,
            'user' => $user->full_name,
            'text' => $comment->comment_text,
            'at' => $comment->created_at?->diffForHumans() ?? 'Just now',
        ]);
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'], // 20MB max
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'entity_type' => 'Task',
            'entity_id' => $task->task_id,
            'file_name' => $fileName,
            'file_path' => $path,
            'uploaded_by' => $user->user_id,
        ]);

        Activity::log('Uploaded task attachment', 'Task', $task->task_id, "{$fileName} on {$task->task_name}");

        return response()->json([
            'message' => 'File uploaded successfully',
            'attachment' => [
                'id' => $attachment->attachment_id,
                'file_name' => $attachment->file_name,
                'file_url' => Storage::url($attachment->file_path),
                'uploader' => $user->full_name,
                'uploaded_at' => 'Just now',
            ],
        ], 201);
    }

    public function deleteAttachment(Task $task, Attachment $attachment)
    {
        $user = Auth::user();
        abort_unless((int) $attachment->entity_id === (int) $task->task_id, 404);
        abort_unless((int) $attachment->uploaded_by === (int) $user->user_id || $user->isDirectorOrAdmin(), 403);

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment removed']);
    }

    public function downloadAttachment(Attachment $attachment)
    {
        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404);

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function storeSubtask(Request $request, Task $task)
    {
        $data = $request->validate([
            'task_name' => ['required', 'string', 'max:150'],
        ]);

        $subtask = Task::create([
            'project_id' => $task->project_id,
            'team_id' => $task->team_id,
            'phase_id' => $task->phase_id,
            'parent_task_id' => $task->task_id,
            'task_name' => $data['task_name'],
            'priority' => $task->priority ?: 'Medium',
            'status' => 'To Do',
            'start_date' => now()->toDateString(),
            'end_date' => $task->end_date,
        ]);

        return response()->json([
            'id' => $subtask->task_id,
            'name' => $subtask->task_name,
            'status' => $subtask->status,
            'is_completed' => false,
        ], 201);
    }

    public function toggleSubtask(Task $subtask)
    {
        $newStatus = in_array($subtask->status, ['Done', 'Completed']) ? 'To Do' : 'Completed';
        $subtask->update(['status' => $newStatus, 'progress' => $newStatus === 'Completed' ? 100 : 0]);

        return response()->json([
            'id' => $subtask->task_id,
            'name' => $subtask->task_name,
            'status' => $subtask->status,
            'is_completed' => in_array($subtask->status, ['Done', 'Completed']),
        ]);
    }

    private function resolveAssigneeId($input, ?Project $project = null): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_numeric($input)) {
            $user = User::find((int) $input);
            if ($user) {
                return $user->user_id;
            }
        }

        $trimmed = trim((string) $input);
        if ($trimmed === '' || $trimmed === '— Unassigned —' || $trimmed === 'Unassigned') {
            return null;
        }

        $user = User::where('email', $trimmed)
            ->orWhere('full_name', $trimmed)
            ->orWhereRaw('LOWER(full_name) = ?', [strtolower($trimmed)])
            ->first();

        if ($user) {
            return $user->user_id;
        }

        $user = User::where('full_name', 'LIKE', "%{$trimmed}%")->first();
        if ($user) {
            return $user->user_id;
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $trimmed));
        $slug = trim($slug, '.');
        if (empty($slug)) {
            $slug = 'member.'.rand(100, 999);
        }

        $email = $slug.'@ju.edu.et';
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = $slug.$counter.'@ju.edu.et';
            $counter++;
        }

        $newUser = User::create([
            'full_name' => $trimmed,
            'email' => $email,
            'password_hash' => bcrypt('ChangeMe123!'),
            'status' => 'Active',
        ]);

        $role = Role::where('role_name', 'Team Member')->first();
        if ($role) {
            $newUser->roles()->attach($role->role_id);
        }

        if ($project && $project->team_id) {
            TeamMember::firstOrCreate([
                'team_id' => $project->team_id,
                'user_id' => $newUser->user_id,
            ], [
                'joined_date' => now()->toDateString(),
            ]);
        }

        Activity::log('Created team member via task assignment', 'User', $newUser->user_id, "{$newUser->full_name} ({$email})");

        return $newUser->user_id;
    }

    public function destroy(Request $request, Task $task)
    {
        $task->load('phase.project', 'project');
        $project = $task->project ?? optional($task->phase)->project;
        $user = Auth::user();

        $canManage = ($project && $project->isManagedBy($user)) || $user->can('create_tasks') || $user->isDirectorOrAdmin();
        abort_unless($canManage, 403);

        $taskName = $task->task_name;
        Activity::log('Deleted task', 'Task', $task->task_id, $taskName);

        $task->comments()->delete();
        $task->progressLogs()->delete();
        $task->dependencies()->detach();
        $task->dependents()->detach();
        $task->subtasks()->update(['parent_task_id' => null]);
        $task->delete();

        if ($project) {
            $project->recalculateProgress();
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Task deleted successfully']);
        }

        return back()->with('status', "\"{$taskName}\" was deleted.");
    }
}
