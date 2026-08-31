<?php

namespace App\Providers;

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Services\RbacService;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // System Administrators bypass all permission checks. Note: we must
        // not call $user->can() here — that re-enters the Gate and causes
        // infinite recursion. Check the RBAC engine / role directly instead.
        $rbac = app(RbacService::class);

        Gate::before(function (User $user, string $ability) use ($rbac): ?bool {
            if (
                $rbac->can($user, 'manage_system_settings')
                || (method_exists($user, 'hasRole') && $user->hasRole('System Administrator'))
            ) {
                return true;
            }

            return null;
        });

        // One Gate per permission slug. Gate callbacks receive optional
        // model arguments, so `can:edit_projects` on routes resolves the
        // project from the route binding when present and delegates to the
        // RBAC engine (org + project + team scopes, inheritance-aware).
        foreach (array_keys(Permissions::ALL) as $slug) {
            Gate::define($slug, function (User $user, $model = null) use ($rbac, $slug) {
                $project = $model instanceof Project
                    ? $model
                    : ($model instanceof Phase ? $model->project : null);

                return $rbac->can($user, $slug, $project);
            });
        }

        // Explicit policy registrations for object-level authorization.
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
