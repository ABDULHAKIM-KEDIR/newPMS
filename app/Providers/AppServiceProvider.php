<?php

namespace App\Providers;

use App\Models\Phase;
use App\Models\Project;
use App\Models\User;
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
        // One Gate per permission slug. Gate callbacks receive optional
        // model arguments, so `can:edit_projects` on routes resolves the
        // project from the route binding when present and delegates to the
        // RBAC engine (org + project + team scopes, inheritance-aware).
        $rbac = app(RbacService::class);

        foreach (array_keys(Permissions::ALL) as $slug) {
            Gate::define($slug, function (User $user, $model = null) use ($rbac, $slug) {
                $project = $model instanceof Project
                    ? $model
                    : ($model instanceof Phase ? $model->project : null);

                return $rbac->can($user, $slug, $project);
            });
        }
    }
}
