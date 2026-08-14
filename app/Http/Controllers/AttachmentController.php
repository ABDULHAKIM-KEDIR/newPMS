<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Attachment;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class AttachmentController extends Controller
{
    public function upload(Request $request, Task $task)
    {
        // Authorization: only managers and the task assignee can upload
        $project = $task->phase->project;
        $user = Auth::user();

        abort_unless(
            $project->isManagedBy($user) || $task->assigned_to === $user->user_id,
            403,
            'You are not allowed to upload attachments for this task.'
        );

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'entity_type' => Task::class,
            'entity_id' => $task->task_id,
            'file_name' => $originalName,
            'file_path' => $path,
            'uploaded_by' => $user->user_id,
        ]);

        // Log activity
        Activity::log('Uploaded attachment', 'Task', $task->task_id, "File: {$originalName}");

        return response()->json([
            'attachment' => [
                'id' => $attachment->attachment_id,
                'file_name' => $attachment->file_name,
                'file_url' => Storage::disk('public')->url($attachment->file_path),
                'uploader' => $user->full_name,
                'created_at' => $attachment->created_at->diffForHumans(),
            ]
        ]);
    }

    public function download(Task $task, Attachment $attachment)
    {
        // Ensure the attachment belongs to this task
        if ($attachment->entity_id != $task->task_id || $attachment->entity_type !== Task::class) {
            abort(404);
        }

        // Authorization: same as upload
        $project = $task->phase->project;
        $user = Auth::user();
        abort_unless(
            $project->isManagedBy($user) || $task->assigned_to === $user->user_id,
            403,
            'You are not allowed to download this attachment.'
        );

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function destroy(Task $task, Attachment $attachment)
    {
        if ($attachment->entity_id != $task->task_id || $attachment->entity_type !== Task::class) {
            abort(404);
        }

        $user = Auth::user();
        $project = $task->phase->project;

        // Allow if manager OR the uploader (not just assignee)
        abort_unless(
            $project->isManagedBy($user) || $attachment->uploaded_by === $user->user_id,
            403,
            'You are not allowed to delete this attachment.'
        );

        // Delete the physical file
        Storage::disk('public')->delete($attachment->file_path);

        // Delete the database record
        $attachment->delete();

        Activity::log('Deleted attachment', 'Task', $task->task_id, "File: {$attachment->file_name}");

        return response()->json(['success' => true]);
    }
}