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
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Static /projects/create must be registered before the /projects/{project} wildcard.
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/change-requests', [ProjectController::class, 'storeChangeRequest'])->name('projects.changeRequests.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show'); // JSON, used by the slide-over panel
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments');
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');

    Route::post('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve'])->name('changeRequests.approve');
    Route::post('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject'])->name('changeRequests.reject');

    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('/teams/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('teams.members.remove');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');

    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    Route::get('/admin/roles', [RoleController::class, 'index'])->name('admin.roles');
    Route::post('/admin/roles', [RoleController::class, 'storeRole'])->name('admin.roles.store');
    Route::post('/admin/roles/users/{user}', [RoleController::class, 'updateUserRole'])->name('admin.roles.updateUser');
    Route::post('/admin/roles/{role}/permissions/{permission}', [RoleController::class, 'togglePermission'])->name('admin.roles.togglePermission');
    Route::get('/admin/audit-log', [AuditLogController::class, 'index'])->name('admin.audit');
    Route::get('/admin/audit-log/export', [AuditLogController::class, 'export'])->name('admin.audit.export');
});
