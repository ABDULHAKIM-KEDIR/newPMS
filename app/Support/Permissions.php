<?php

namespace App\Support;

/**
 * The canonical permission catalogue — the single source of truth.
 *
 * Permissions are grouped (for the admin permission-matrix UI) and are no
 * longer tied to any hard-coded role hierarchy. Seeded default roles in
 * RbacSeeder map slug sets onto roles, but administrators may create
 * unlimited custom roles and grant any subset of this catalogue.
 */
class Permissions
{
    /** slug => [description, group] */
    public const ALL = [
        'view_projects' => ['View all projects and their details', 'Projects'],
        'create_projects' => ['Create new projects', 'Projects'],
        'edit_projects' => ['Edit project details, status, and timeline', 'Projects'],
        'delete_projects' => ['Delete a project', 'Projects'],
        'view_tasks' => ['View tasks', 'Tasks'],
        'create_tasks' => ['Create new tasks', 'Tasks'],
        'assign_tasks' => ['Assign or reassign tasks to team members', 'Tasks'],
        'update_task_status' => ['Update the status of a task', 'Tasks'],
        'manage_team' => ['Add or remove team members', 'Teams'],
        'approve_change_requests' => ['Approve or reject change requests', 'Governance'],
        'manage_budgets' => ['Edit project and phase budgets', 'Governance'],
        'view_budgets' => ['View project and phase budgets', 'Governance'],
        'view_reports' => ['View performance reports and analytics', 'Insights'],
        'view_calendar' => ['View project milestones and task calendar', 'Insights'],
        'view_notifications' => ['View and manage personal notifications', 'Insights'],
        'view_audit_logs' => ['View and export the system audit log', 'Administration'],
        'manage_users' => ['Create, edit, deactivate users, and assign roles', 'Administration'],
        'users.reset-password' => ['Reset a user\'s password to a temporary default', 'Administration'],
        'manage_roles' => ['Create roles and manage their permissions', 'Administration'],
        'manage_system_settings' => ['Manage system-level configuration', 'Administration'],
    ];

    /** Grouped view for the permission matrix. */
    public const GROUPS = [
        'Projects' => ['view_projects', 'create_projects', 'edit_projects', 'delete_projects'],
        'Tasks' => ['view_tasks', 'create_tasks', 'assign_tasks', 'update_task_status'],
        'Teams' => ['manage_team'],
        'Governance' => ['approve_change_requests', 'manage_budgets', 'view_budgets'],
        'Insights' => ['view_reports', 'view_calendar', 'view_notifications'],
        'Administration' => ['view_audit_logs', 'manage_users', 'users.reset-password', 'manage_roles', 'manage_system_settings'],
    ];

    /**
     * Default roles seeded on a clean install. Marked is_system (protected
     * from deletion) but their permission sets remain editable, and
     * administrators may add unlimited custom roles on top.
     *
     * `parent` models Asana-style composition instead of a fixed chain.
     */
    public const DEFAULT_ROLES = [
        'Administrator' => [
            'scope' => 'organization',
            'rank' => 10,
            'parent' => null,
            'permissions' => '*',
        ],
        'Project Manager' => [
            'scope' => 'organization',
            'rank' => 20,
            'parent' => 'Team Lead',
            'permissions' => [
                'create_projects', 'delete_projects', 'approve_change_requests',
                'manage_budgets', 'view_budgets', 'view_reports', 'view_audit_logs',
            ],
        ],
        'Team Lead' => [
            'scope' => 'organization',
            'rank' => 40,
            'parent' => 'Team Member',
            'permissions' => [
                'edit_projects', 'create_tasks', 'assign_tasks', 'manage_team',
                'view_budgets',
            ],
        ],
        'Team Member' => [
            'scope' => 'organization',
            'rank' => 60,
            'parent' => null,
            'permissions' => [
                'view_projects', 'view_tasks', 'update_task_status',
                'view_reports', 'view_calendar', 'view_notifications',
            ],
        ],
        'Project Contributor' => [
            'scope' => 'project',
            'rank' => 70,
            'parent' => 'Project Viewer',
            'permissions' => ['update_task_status', 'create_tasks'],
        ],
        'Project Viewer' => [
            'scope' => 'project',
            'rank' => 80,
            'parent' => null,
            'permissions' => ['view_projects', 'view_tasks', 'view_calendar', 'view_notifications'],
        ],
    ];

    /** Legacy directorate names kept so existing data keeps resolving. */
    public const LEGACY_ROLE_ALIASES = [
        'System Administrator' => 'Administrator',
        'Admin' => 'Administrator',
        'ICT Director' => 'Administrator',
        'Team Leader' => 'Team Lead',
    ];
}
