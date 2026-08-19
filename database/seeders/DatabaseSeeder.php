<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles & permissions ----
        // Permission slugs and default role grants are the single source of
        // truth in App\Support\Permissions — the same list AppServiceProvider
        // reads to register a Gate per slug. permission_name IS the slug
        // (e.g. 'edit_projects'), and its description is stored separately.
        $roles = collect(array_keys(Permissions::ROLE_GRANTS))->mapWithKeys(
            fn ($name) => [$name => Role::create(['role_name' => $name, 'description' => "$name role"])]
        );

        $permissions = collect(Permissions::ALL)->mapWithKeys(
            fn ($description, $slug) => [$slug => Permission::create([
                'permission_name' => $slug,
                'description' => $description,
            ])]
        );

        foreach (Permissions::ROLE_GRANTS as $roleName => $slugs) {
            foreach ($slugs as $slug) {
                $roles[$roleName]->permissions()->attach($permissions[$slug]->permission_id);
            }
        }

        // ---- The one bootstrap account ----
        // No demo users, teams, or projects — this is a clean install. The
        // System Administrator's job from here is exactly what their role
        // grants: create the first real users (via Users → + New User) and
        // assign someone an ICT Director / Team Leader role so they can in
        // turn create teams and projects.
        $admin = User::create([
            'full_name' => 'System Administrator',
            'email' => 'admin@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => null,
            'status' => 'Active',
        ]);
        $admin->roles()->attach($roles['System Administrator']->role_id);
    }
}
