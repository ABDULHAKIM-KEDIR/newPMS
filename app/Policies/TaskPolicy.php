<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Services\RbacService;

/**
 * Object-level authorization for tasks. Visibility follows the parent
 * project; a task without a resolvable project (orphaned phase data) is
 * only visible to organization-wide task viewers.
 */
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        // Assigned user always has access.
        if ((int) $task->assigned_to === (int) $user->user_id) {
            return true;
        }

        $project = $task->project ?? optional($task->phase)->project;

        if ($project) {
            return $user->can('view', $project)
                || app(RbacService::class)->can($user, 'view_tasks', $project);
        }

        return $user->hasPermission('view_tasks');
    }

    /** The task's project manager, org edit-project holders, or the assignee. */
    public function update(User $user, Task $task): bool
    {
        $project = $task->project ?? optional($task->phase)->project;

        if ((int) $task->assigned_to === (int) $user->user_id) {
            return true;
        }

        if ($project) {
            return $project->isManagedBy($user);
        }

        return $user->hasPermission('edit_projects');
    }

    /** Commenting follows view access. */
    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /** Attachments follow view access (upload) / uploader-or-admin (delete). */
    public function attach(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function deleteAttachment(User $user, Task $task): bool
    {
        return $user->isDirectorOrAdmin();
    }

    public function assign(User $user, Task $task): bool
    {
        $project = $task->project ?? optional($task->phase)->project;

        if ($project) {
            return app(RbacService::class)
                ->can($user, 'assign_tasks', $project);
        }

        return $user->hasPermission('assign_tasks');
    }

    /** Status transitions: the assignee, or anyone who manages the project. */
    public function updateStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
