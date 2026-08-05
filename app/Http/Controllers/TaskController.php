<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
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
        $task->load(['subtasks', 'comments.user', 'dependencies', 'assignee', 'phase.project']);

        return response()->json([
            'id' => $task->task_id,
            'name' => $task->task_name,
            'status' => $task->status,
            'priority' => $task->priority,
            'assignee' => optional($task->assignee)->full_name,
            'phase' => optional($task->phase)->phase_name,
            'due' => optional($task->end_date)?->format('d M Y'),
            'description' => $task->description,
            'subtasks' => $task->subtasks->map(fn ($t) => ['name' => $t->task_name, 'status' => $t->status]),
            'comments' => $task->comments->map(fn ($c) => [
                'user' => optional($c->user)->full_name,
                'text' => $c->comment_text,
                'at' => $c->created_at?->diffForHumans(),
            ]),
        ]);
    }
}
