<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
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
            fn ($description, $slug) => [
                $slug => Permission::create([
                    'permission_name' => $slug,
                    'description' => $description,
                ]),
            ]
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

        // ---- Demo Accounts & Sample Data ----
        $director = User::create([
            'full_name' => 'ICT Director',
            'email' => 'director@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251911000000',
            'status' => 'Active',
        ]);
        $director->roles()->attach($roles['ICT Director']->role_id);

        $leader = User::create([
            'full_name' => 'Team Leader',
            'email' => 'leader@ju.edu.et',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => '+251912000000',
            'status' => 'Active',
        ]);
        $leader->roles()->attach($roles['Team Leader']->role_id);

        $team = Team::create([
            'team_name' => 'Software Engineering',
            'description' => 'Core software development team',
            'team_leader_id' => $leader->user_id,
        ]);

        TeamMember::create([
            'team_id' => $team->team_id,
            'user_id' => $leader->user_id,
            'joined_date' => now()->toDateString(),
        ]);

        // ---- Create Project ----
        $project = Project::create([
            'project_name' => 'Jimma University PMS',
            'description' => 'Development of ICT Project Management System',
            'project_type' => 'Software',
            'team_id' => $team->team_id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 'planning',
            'created_by' => $director->user_id,
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'allocated_amount' => 500000,
            'spent_amount' => 0,
            'currency' => 'ETB',
        ]);

        $phaseNames = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
        $initiationPhase = null;

        foreach ($phaseNames as $i => $phaseName) {
            $phase = Phase::create([
                'project_id' => $project->project_id,
                'phase_name' => $phaseName,
                'status' => $i === 0 ? 'In Progress' : 'Not started',
                'sequence_order' => $i,
            ]);

            PhaseBudget::create([
                'phase_id' => $phase->phase_id,
                'allocated_amount' => 100000,
                'spent_amount' => 0,
            ]);

            if ($phaseName === 'Initiation') {
                $initiationPhase = $phase;
            }
        }

        // ---- Create Task ----
        $task = Task::create([
            'phase_id' => $initiationPhase->phase_id,
            'task_name' => 'Requirements Gathering',
            'description' => 'Gather initial system requirements',
            'assigned_to' => $leader->user_id,
            'status' => 'Pending',
            'priority' => 'High',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
        ]);

        // ---- Update Project & Task ----
        $project->update([
            'status' => 'active',
            'description' => 'Development of ICT Project Management System (Active)',
        ]);

        $task->update([
            'status' => 'In Progress',
            'description' => 'Gather initial system requirements and finalize SRS',
        ]);
    }
}
