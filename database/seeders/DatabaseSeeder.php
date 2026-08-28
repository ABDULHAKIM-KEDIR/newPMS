<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles & permissions ----
        // The dynamic RBAC catalogue: permissions + default roles (with
        // inheritance and scopes) live in RbacSeeder, which also aliases
        // legacy directorate role names onto canonical ones.
        $this->call(RbacSeeder::class);

        $adminRole = Role::where('role_name', 'Administrator')->firstOrFail();

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
        $admin->roles()->attach($adminRole->role_id);
    }
}