<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Office;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectTeam;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\OrgHierarchyService;
use App\Services\RbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles & permissions ----
        // The dynamic RBAC catalogue: permissions + default roles (with
        // inheritance and scopes) live in RbacSeeder, which also aliases
        // legacy role names onto canonical ones.
        $this->call(RbacSeeder::class);
        $this->call(ProjectTypeSeeder::class);
        $this->call(OfficeSeeder::class);

        $adminRole = Role::where('role_name', 'Administrator')->firstOrFail();

        // ---- The one bootstrap account ----
        // No demo users, teams, or projects — this is a clean install. The
        // System Administrator's job from here is exactly what their role
        // grants: create the first real users (via Users → + New User) and
        // assign someone an Administrator / Team Lead role so they can in
        // turn create teams and projects.
        $admin = User::create([
            'full_name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('ChangeMe123!'),
            'phone' => null,
            'status' => 'Active',
        ]);
        $admin->roles()->attach($adminRole->role_id);

        // The bootstrap account belongs to no office (it oversees them all);
        // assign specific offices to users via Users → Edit.
        $admin->office_id = null;
        $admin->save();

        $this->seedRoleAndOfficeMatrix($admin);
    }

    private function seedRoleAndOfficeMatrix(User $admin): void
    {
        $password = Hash::make('ChangeMe123!');
        $roles = Role::orderBy('rank')->orderBy('role_id')->get();
        $offices = Office::active()->orderBy('office_id')->get();
        $projectType = ProjectType::where('is_active', true)->orderBy('project_type_id')->firstOrFail();
        $department = Department::firstOrCreate(
            ['department_code' => 'SAMPLE'],
            ['department_name' => 'Sample Organization', 'status' => 'Active']
        );

        $offices->each(function (Office $office) use ($department): void {
            if (! $office->department_id) {
                $office->update(['department_id' => $department->department_id]);
            }
        });

        $roleUsers = [];
        foreach ($roles as $role) {
            $user = $role->role_name === 'Administrator'
                ? $admin
                : User::firstOrCreate(
                    ['email' => 'sample.'.str($role->role_name)->slug('-').'@example.com'],
                    [
                        'full_name' => 'Sample '.$role->role_name,
                        'password_hash' => $password,
                        'status' => 'Active',
                        'department' => 'Sample Organization',
                        'office_id' => $offices->first()?->office_id,
                    ]
                );

            $roleUsers[$role->role_name] = $user;
        }

        $director = User::firstOrCreate(
            ['email' => 'director@example.com'],
            [
                'full_name' => 'Sample Project Director',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Sample Organization',
                'office_id' => $offices->first()?->office_id,
            ]
        );
        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'full_name' => 'Sample Team Member',
                'password_hash' => $password,
                'status' => 'Active',
                'department' => 'Sample Organization',
                'office_id' => $offices->first()?->office_id,
            ]
        );
        $legacyUsers = [
            ['abebe@example.com', 'Abebe Bikila'],
            ['chaltu@example.com', 'Chaltu Bekele'],
            ['caala@example.com', 'Caala Tadesse'],
            ['john@example.com', 'John Doe'],
            ['daniel@example.com', 'Daniel Tesfaye'],
            ['sophia@example.com', 'Sophia Chen'],
        ];
        foreach ($legacyUsers as [$email, $name]) {
            User::firstOrCreate(
                ['email' => $email],
                ['full_name' => $name, 'password_hash' => $password, 'status' => 'Active', 'department' => 'Sample Organization', 'office_id' => $offices->first()?->office_id]
            );
        }

        if ($projectManagerRole = $roles->firstWhere('role_name', 'Project Manager')) {
            app(RbacService::class)->assignRole($director, $projectManagerRole);
        }
        if ($teamMemberRole = $roles->firstWhere('role_name', 'Team Member')) {
            app(RbacService::class)->assignRole($member, $teamMemberRole);
        }

        $teams = collect();
        $projects = collect();
        foreach ($offices as $office) {
            $teamLead = $roleUsers['Team Lead'] ?? $admin;
            $team = Team::updateOrCreate(
                ['team_name' => 'Sample Team - '.$office->office_code],
                [
                    'team_leader_id' => $teamLead->user_id,
                    'description' => 'Development team for '.$office->office_name.'.',
                    'status' => 'Active',
                    'office_id' => $office->office_id,
                ]
            );
            $team->users()->syncWithoutDetaching([$teamLead->user_id, $member->user_id]);
            $teams->push($team);

            $project = Project::updateOrCreate(
                ['project_name' => 'Sample Project - '.$office->office_code],
                [
                    'description' => 'Demonstration project owned by '.$office->office_name.'.',
                    'client' => $office->office_name,
                    'project_type' => $projectType->name,
                    'project_type_id' => $projectType->project_type_id,
                    'team_id' => $team->team_id,
                    'project_manager_id' => ($roleUsers['Project Manager'] ?? $teamLead)->user_id,
                    'created_by' => $admin->user_id,
                    'status' => 'Planning',
                    'priority' => 'Medium',
                    'progress' => 0,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonths(3)->toDateString(),
                    'primary_office_id' => $office->office_id,
                    'department_id' => $office->department_id ?? $department->department_id,
                ]
            );
            $project->offices()->syncWithoutDetaching([
                $office->office_id => ['participation_type' => 'primary'],
            ]);
            ProjectTeam::updateOrCreate(
                ['project_id' => $project->project_id, 'team_id' => $team->team_id],
                ['assigned_date' => now(), 'access_level' => 'manage']
            );
            $projects->push($project);
        }

        $firstProject = $projects->first();
        $firstTeam = $teams->first();
        $firstOffice = $offices->first();
        $hierarchy = app(OrgHierarchyService::class);

        foreach ($roles as $role) {
            $user = $roleUsers[$role->role_name];
            if ($role->role_name === 'Administrator') {
                app(RbacService::class)->assignRole($user, $role);
            } elseif (str_starts_with($role->role_name, 'Head of ')) {
                $scope = match ($role->role_name) {
                    'Head of Department' => $department,
                    'Head of Office' => $firstOffice,
                    'Head of Project' => $firstProject,
                    'Head of Team' => $firstTeam,
                    default => null,
                };
                if ($scope) {
                    $hierarchy->assignHead($user, $scope);
                }
            } elseif ($role->scope === 'project' && $firstProject) {
                app(RbacService::class)->assignRole($user, $role, $firstProject);
            } else {
                app(RbacService::class)->assignRole($user, $role);
            }
        }

        $hierarchy->sync();
        $this->seedWorkflowFixtures($admin, $password, $department, $firstOffice, $projectType);
        $this->command?->info(sprintf(
            'Sample matrix created: %d role accounts, %d offices, %d teams, %d projects.',
            count($roleUsers), $offices->count(), $teams->count(), $projects->count()
        ));
    }

    private function seedWorkflowFixtures(User $admin, string $password, Department $department, ?Office $office, ProjectType $projectType): void
    {
        $ictOfficeId = $office?->office_id;
        $john = User::updateOrCreate(
            ['email' => 'john.smith@example.com'],
            ['full_name' => 'John Smith', 'password_hash' => $password, 'status' => 'Active', 'department' => 'Sample Organization', 'office_id' => $ictOfficeId]
        );
        $sarah = User::updateOrCreate(
            ['email' => 'sarah@example.com'],
            ['full_name' => 'Sarah Connor', 'password_hash' => $password, 'status' => 'Active', 'department' => 'Sample Organization', 'office_id' => $ictOfficeId]
        );
        $david = User::updateOrCreate(
            ['email' => 'david@example.com'],
            ['full_name' => 'David Kim', 'password_hash' => $password, 'status' => 'Active', 'department' => 'Sample Organization', 'office_id' => $ictOfficeId]
        );
        $projectManagerRole = Role::where('role_name', 'Project Manager')->first();
        $memberRole = Role::where('role_name', 'Team Member')->first();
        if ($projectManagerRole) {
            app(RbacService::class)->assignRole($john, $projectManagerRole);
        }
        if ($memberRole) {
            app(RbacService::class)->assignRole($sarah, $memberRole);
            app(RbacService::class)->assignRole($david, $memberRole);
        }

        $uiux = Team::updateOrCreate(
            ['team_name' => 'UI/UX Team'],
            ['team_leader_id' => $john->user_id, 'description' => 'Design and user research.', 'status' => 'Active', 'office_id' => $ictOfficeId]
        );
        $frontend = Team::updateOrCreate(
            ['team_name' => 'Frontend Team'],
            ['team_leader_id' => $john->user_id, 'description' => 'Web interface delivery.', 'status' => 'Active', 'office_id' => $ictOfficeId]
        );
        $backend = Team::updateOrCreate(
            ['team_name' => 'Backend Team'],
            ['team_leader_id' => $john->user_id, 'description' => 'Services and APIs.', 'status' => 'Active', 'office_id' => $ictOfficeId]
        );
        $network = Team::updateOrCreate(
            ['team_name' => 'Network & Infrastructure'],
            ['team_leader_id' => $john->user_id, 'description' => 'Infrastructure operations.', 'status' => 'Active', 'office_id' => $ictOfficeId]
        );
        $engineering = Team::updateOrCreate(
            ['team_name' => 'Software Engineering'],
            ['team_leader_id' => $john->user_id, 'description' => 'Enterprise software engineering.', 'status' => 'Active', 'office_id' => $ictOfficeId]
        );
        foreach ([$uiux, $frontend, $backend] as $team) {
            $team->users()->syncWithoutDetaching([$john->user_id, $sarah->user_id, $david->user_id]);
        }

        $ecommerce = Project::updateOrCreate(
            ['project_name' => 'E-Commerce Website'],
            [
                'description' => 'Online retail platform.', 'client' => 'Global Retail Corporation',
                'project_type' => $projectType->name, 'project_type_id' => $projectType->project_type_id,
                'team_id' => $frontend->team_id, 'project_manager_id' => $john->user_id, 'created_by' => $admin->user_id,
                'status' => 'active', 'priority' => 'High', 'progress' => 45,
                'start_date' => now()->toDateString(), 'end_date' => now()->addMonths(4)->toDateString(),
                'primary_office_id' => $ictOfficeId, 'department_id' => $department->department_id,
            ]
        );
        foreach ([$uiux, $frontend, $backend] as $team) {
            ProjectTeam::updateOrCreate(
                ['project_id' => $ecommerce->project_id, 'team_id' => $team->team_id],
                ['assigned_date' => now(), 'access_level' => 'manage']
            );
        }
        if ($ictOfficeId) {
            $ecommerce->offices()->syncWithoutDetaching([$ictOfficeId => ['participation_type' => 'primary']]);
        }

        $matrixProject = Project::where('project_name', 'Sample Project - ICT')->first();
        if ($matrixProject) {
            $matrixProject->update(['project_name' => 'Sample PMS']);
        }

        $samplePms = Project::updateOrCreate(
            ['project_name' => 'Sample PMS'],
            [
                'description' => 'Project management system demonstration.', 'client' => 'Sample Organization',
                'project_type' => $projectType->name, 'project_type_id' => $projectType->project_type_id,
                'team_id' => $frontend->team_id, 'project_manager_id' => $john->user_id, 'created_by' => $admin->user_id,
                'status' => 'active', 'priority' => 'Medium', 'progress' => 20,
                'start_date' => now()->toDateString(), 'end_date' => now()->addMonths(3)->toDateString(),
                'primary_office_id' => $ictOfficeId, 'department_id' => $department->department_id,
            ]
        );

        $samplePlanning = Phase::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'phase_name' => 'Requirements'],
            ['start_date' => now()->toDateString(), 'end_date' => now()->addWeeks(2)->toDateString(), 'duration' => 14, 'status' => 'In Progress', 'sequence_order' => 1]
        );
        Phase::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'phase_name' => 'Development'],
            ['start_date' => now()->addWeeks(3)->toDateString(), 'end_date' => now()->addWeeks(6)->toDateString(), 'duration' => 21, 'status' => 'Not started', 'sequence_order' => 2]
        );
        Task::firstOrCreate(
            ['project_id' => $samplePms->project_id, 'task_name' => 'Requirements Gathering'],
            ['phase_id' => $samplePlanning->phase_id, 'team_id' => $frontend->team_id, 'description' => 'Gather and document requirements.', 'assigned_to' => $sarah->user_id, 'status' => 'In Progress', 'priority' => 'High', 'progress' => 40, 'start_date' => now()->toDateString(), 'end_date' => now()->addWeek()->toDateString()]
        );

        $this->seedPhasesAndTasks($ecommerce, $uiux, $frontend, $backend, $sarah, $david);
    }

    private function seedPhasesAndTasks(Project $project, Team $uiux, Team $frontend, Team $backend, User $sarah, User $david): void
    {
        $phaseNames = ['Design', 'Development', 'Testing', 'Deployment & UAT'];
        foreach ($phaseNames as $index => $name) {
            $phase = Phase::firstOrCreate(
                ['project_id' => $project->project_id, 'phase_name' => $name],
                ['start_date' => now()->addWeeks($index)->toDateString(), 'end_date' => now()->addWeeks($index + 2)->toDateString(), 'duration' => 14, 'status' => $index === 0 ? 'In Progress' : 'Not started', 'sequence_order' => $index + 1]
            );
            $tasks = match ($name) {
                'Design' => [['Homepage Wireframe', $uiux, $sarah], ['Product Page Design', $uiux, $sarah], ['Checkout Design', $uiux, $sarah]],
                'Development' => [['Build Homepage', $frontend, $david], ['Build Product Listing', $frontend, $david], ['Build Shopping Cart', $frontend, $david]],
                'Testing' => [['Create Authentication API', $backend, $david], ['Create Product API', $backend, $david], ['Create Order API', $backend, $david], ['Create Payment API', $backend, $david]],
                default => [['Deployment Checklist', $backend, $david]],
            };
            foreach ($tasks as [$name, $team, $assignee]) {
                Task::firstOrCreate(
                    ['project_id' => $project->project_id, 'task_name' => $name],
                    ['phase_id' => $phase->phase_id, 'team_id' => $team->team_id, 'description' => $name.'.', 'assigned_to' => $assignee->user_id, 'status' => 'Pending', 'priority' => 'Medium', 'progress' => 0, 'start_date' => now()->toDateString(), 'end_date' => now()->addWeeks(2)->toDateString()]
                );
            }
        }
    }
}
