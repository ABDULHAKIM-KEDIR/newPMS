<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// 'active' runs alongside 'auth' on every one of these — a session for a
// user a System Administrator just deactivated is killed on the very next
// request, not just blocked at the next login.
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Static /projects/create must be registered before the /projects/{project} wildcard.
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create')->middleware('can:create_projects');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store')->middleware('can:create_projects');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index')->middleware('can:view_projects');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit')->middleware('can:edit_projects');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update')->middleware('can:edit_projects');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy')->middleware('can:delete_projects');
    Route::post('/projects/{project}/change-requests', [ProjectController::class, 'storeChangeRequest'])->name('projects.changeRequests.store')->middleware('can:view_projects');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show')->middleware('can:view_projects');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index')->middleware('can:view_tasks');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store')->middleware('can:create_tasks');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show')->middleware('can:view_tasks'); // JSON, used by the slide-over panel
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status')->middleware('can:update_task_status');
    Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments')->middleware('can:view_tasks');
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign')->middleware('can:assign_tasks');

    Route::post('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve'])->name('changeRequests.approve')->middleware('can:approve_change_requests');
    Route::post('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject'])->name('changeRequests.reject')->middleware('can:approve_change_requests');

    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create')->middleware('can:manage_team');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store')->middleware('can:manage_team');
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index')->middleware('can:view_projects');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add')->middleware('can:manage_team');
    Route::delete('/teams/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('teams.members.remove')->middleware('can:manage_team');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show')->middleware('can:view_projects');

    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    Route::get('/admin/roles', [RoleController::class, 'index'])->name('admin.roles');
    Route::post('/admin/roles', [RoleController::class, 'storeRole'])->name('admin.roles.store')->middleware('can:manage_roles');
    Route::post('/admin/roles/users/{user}', [RoleController::class, 'updateUserRole'])->name('admin.roles.updateUser')->middleware('can:manage_users');
    Route::post('/admin/roles/{role}/permissions/{permission}', [RoleController::class, 'togglePermission'])->name('admin.roles.togglePermission')->middleware('can:manage_roles');

    Route::get('/admin/audit-log', [AuditLogController::class, 'index'])->name('admin.audit')->middleware('can:view_audit_logs');
    Route::get('/admin/audit-log/export', [AuditLogController::class, 'export'])->name('admin.audit.export')->middleware('can:view_audit_logs');

    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index')->middleware('can:manage_users');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create')->middleware('can:manage_users');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store')->middleware('can:manage_users');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit')->middleware('can:manage_users');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update')->middleware('can:manage_users');
    Route::post('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggleStatus')->middleware('can:manage_users');

    Route::get('/admin/settings', [SystemSettingController::class, 'edit'])->name('admin.settings')->middleware('can:manage_system_settings');
    Route::put('/admin/settings', [SystemSettingController::class, 'update'])->name('admin.settings.update')->middleware('can:manage_system_settings');
});
