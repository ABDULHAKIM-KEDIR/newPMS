<?php

namespace App\Support;

/**
 * The canonical list of permission slugs in the system, and which of the
 * four directorate roles get which. This is the single source of truth —
 * DatabaseSeeder reads it to grant permissions, and AppServiceProvider reads
 * it to register a Laravel Gate per slug (so `can:slug` route middleware and
 * `@can('slug')` in Blade both work without a dedicated Policy class).
 */
class Permissions
{
    // Machine-readable slug => human-readable description, shown on the
    // Roles & Access permission matrix.
    public const ALL = [
        'view_projects' => 'View all projects and their details',
        'create_projects' => 'Create new projects',
        'edit_projects' => 'Edit project details, status, and timeline',
        'delete_projects' => 'Delete a project',
        'view_tasks' => 'View tasks',
        'create_tasks' => 'Create new tasks',
        'assign_tasks' => 'Assign or reassign tasks to team members',
        'update_task_status' => 'Update the status of a task',
        'manage_team' => 'Add or remove team members',
        'approve_change_requests' => 'Approve or reject change requests',
        'manage_budgets' => 'Edit project and phase budgets',
        'view_reports' => 'View performance reports and analytics',
        'view_calendar' => 'View project milestones and task calendar',
        'view_audit_logs' => 'View and export the system audit log',
        'manage_users' => 'Create, edit, deactivate users, and assign roles',
        'manage_roles' => 'Create roles and manage their permissions',
        'manage_system_settings' => 'Manage system-level configuration',
    ];

    // Which permissions each role is seeded with.
    public const ROLE_GRANTS = [
        'Admin' => [
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            'view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status',
            'manage_team', 'approve_change_requests', 'manage_budgets',
            'view_reports', 'view_calendar',
            'manage_users', 'manage_roles', 'view_audit_logs', 'manage_system_settings',
        ],
        'System Administrator' => [
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            'view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status',
            'manage_team', 'approve_change_requests', 'manage_budgets',
            'view_reports', 'view_calendar',
            'manage_users', 'manage_roles', 'view_audit_logs', 'manage_system_settings',
        ],
        'Project Manager' => [
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            'view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status',
            'manage_team', 'approve_change_requests', 'manage_budgets',
            'view_reports', 'view_calendar', 'view_audit_logs',
        ],
        'ICT Director' => [
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            'view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status',
            'manage_team', 'approve_change_requests', 'manage_budgets',
            'view_reports', 'view_calendar', 'view_audit_logs',
        ],
        'Team Lead' => [
            'view_projects', 'create_projects', 'edit_projects',
            'view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status',
            'manage_team', 'view_reports', 'view_calendar',
        ],
        'Team Leader' => [
            'view_projects', 'create_projects', 'edit_projects',
            'view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status',
            'manage_team', 'view_reports', 'view_calendar',
        ],
        'Team Member' => [
            'view_projects', 'view_tasks', 'update_task_status',
            'view_reports', 'view_calendar',
        ],
    ];
}
