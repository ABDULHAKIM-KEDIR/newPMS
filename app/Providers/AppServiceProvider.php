<?php

namespace App\Providers;

use App\Models\User;
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
        // One Gate per permission slug, so both `->middleware('can:slug')` on
        // routes and `@can('slug')` in Blade work directly off the
        // roles/permissions tables without a dedicated Policy per model.
        foreach (array_keys(Permissions::ALL) as $slug) {
            Gate::define($slug, fn (User $user) => $user->hasPermission($slug));
        }
    }
}
