<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ChecksHierarchicalLeadership;
use App\Services\RbacService;

/**
 * Object-level authorization for tasks. Visibility follows the parent
 * project; a task without a resolvable project (orphaned phase data) is
 * only visible to organization-wide task viewers.
 */
class TaskPolicy
{
    use ChecksHierarchicalLeadership;

    public function view(User $user, Task $task): bool
    {
        // Assigned user always has access.
        if ((int) $task->assigned_to === (int) $user->user_id
            || $task->assignments()->where('user_id', $user->user_id)->exists()) {
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
        if ($task->is_locked) {
            return false;
        }

        $project = $task->project ?? optional($task->phase)->project;

        if ((int) $task->assigned_to === (int) $user->user_id) {
            return true;
        }

        // Sub-Team Lead / Team Lead / PM / Office Head / Dept Head.
        if ($this->leadsOrOversees($user, $task)) {
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
        if ($task->is_locked) {
            return false;
        }

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
        if ($task->is_locked) {
            return false;
        }

        return $this->update($user, $task);
    }

    /** Only assignees can accept/reject their assignment on a task. */
    public function accept(User $user, Task $task): bool
    {
        return (int) $task->assigned_to === (int) $user->user_id
            || $task->assignments()->where('user_id', $user->user_id)->exists();
    }

    public function reject(User $user, Task $task): bool
    {
        return $this->accept($user, $task);
    }

    /** Check if user can override locked fields on an accepted task. */
    public function modifyLocked(User $user, Task $task): bool
    {
        $project = $task->project ?? optional($task->phase)->project;

        return $user->isAdmin()
            || $user->isDirectorOrAdmin()
            || ($project && $project->isManagedBy($user));
    }
}
